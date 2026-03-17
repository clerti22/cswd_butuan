<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';

require_login(['super admin']);

function xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function build_full_name(array $record): string
{
    $parts = [
        trim((string) ($record['first_name'] ?? '')),
        trim((string) ($record['middle_name'] ?? '')),
        trim((string) ($record['last_name'] ?? '')),
        trim((string) ($record['suffix'] ?? '')),
    ];

    return trim(implode(' ', array_values(array_filter($parts, static fn(string $part): bool => $part !== ''))));
}

function normalize_multi_value_field(string $value): array
{
    $items = array_filter(array_map('trim', explode(',', $value)), static fn(string $item): bool => $item !== '');
    $normalized = [];

    foreach ($items as $item) {
        $normalized[mb_strtolower($item)] = true;
    }

    return $normalized;
}

function checkbox(bool $checked): string
{
    return $checked ? '☑' : '☐';
}

function dos_datetime_fields(int $timestamp): array
{
    $dateParts = getdate($timestamp);

    $year = max(1980, (int) ($dateParts['year'] ?? 1980));
    $month = max(1, min(12, (int) ($dateParts['mon'] ?? 1)));
    $day = max(1, min(31, (int) ($dateParts['mday'] ?? 1)));
    $hour = max(0, min(23, (int) ($dateParts['hours'] ?? 0)));
    $minute = max(0, min(59, (int) ($dateParts['minutes'] ?? 0)));
    $second = max(0, min(59, (int) ($dateParts['seconds'] ?? 0)));

    $dosTime = ($hour << 11) | ($minute << 5) | intdiv($second, 2);
    $dosDate = (($year - 1980) << 9) | ($month << 5) | $day;

    return [$dosTime, $dosDate];
}

function create_zip_archive_without_extension(array $files): string
{
    $timestamp = time();
    [$dosTime, $dosDate] = dos_datetime_fields($timestamp);

    $localHeaders = '';
    $centralDirectory = '';
    $offset = 0;

    foreach ($files as $name => $content) {
        $name = (string) $name;
        $content = (string) $content;

        $crc32 = crc32($content);
        if ($crc32 < 0) {
            $crc32 += 4294967296;
        }

        $size = strlen($content);
        $nameLength = strlen($name);

        $localHeader = pack(
            'VvvvvvVVVvv',
            0x04034b50,
            20,
            0,
            0,
            $dosTime,
            $dosDate,
            $crc32,
            $size,
            $size,
            $nameLength,
            0
        );

        $localHeaders .= $localHeader . $name . $content;

        $centralHeader = pack(
            'VvvvvvvVVVvvvvvVV',
            0x02014b50,
            20,
            20,
            0,
            0,
            $dosTime,
            $dosDate,
            $crc32,
            $size,
            $size,
            $nameLength,
            0,
            0,
            0,
            0,
            32,
            $offset
        );

        $centralDirectory .= $centralHeader . $name;
        $offset += strlen($localHeader) + $nameLength + $size;
    }

    $centralSize = strlen($centralDirectory);
    $centralOffset = strlen($localHeaders);

    $endOfCentralDirectory = pack(
        'VvvvvVVv',
        0x06054b50,
        0,
        0,
        count($files),
        count($files),
        $centralSize,
        $centralOffset,
        0
    );

    return $localHeaders . $centralDirectory . $endOfCentralDirectory;
}

function build_docx(string $documentXml, string $targetPath): void
{
    $contentTypes = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
  <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>
XML;

    $rels = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;

    $documentRels = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>
XML;

    $styles = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:docDefaults>
    <w:rPrDefault>
      <w:rPr>
        <w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>
        <w:sz w:val="16"/>
      </w:rPr>
    </w:rPrDefault>
  </w:docDefaults>
  <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
    <w:name w:val="Normal"/>
    <w:qFormat/>
  </w:style>
</w:styles>
XML;

    $files = [
        '[Content_Types].xml' => $contentTypes,
        '_rels/.rels' => $rels,
        'word/document.xml' => $documentXml,
        'word/styles.xml' => $styles,
        'word/_rels/document.xml.rels' => $documentRels,
    ];

    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create DOCX file.');
        }

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
        return;
    }

    $archive = create_zip_archive_without_extension($files);
    if (file_put_contents($targetPath, $archive) === false) {
        throw new RuntimeException('Unable to create DOCX file.');
    }
}

function paragraph(string $text, string $align = 'left', bool $bold = false, int $size = 16, int $spaceAfter = 20): string
{
    $boldTag = $bold ? '<w:b/>' : '';
    $safeText = xml_escape($text);

    return '<w:p><w:pPr><w:jc w:val="' . $align . '"/><w:spacing w:after="' . $spaceAfter . '"/></w:pPr><w:r><w:rPr>' . $boldTag . '<w:sz w:val="' . $size . '"/></w:rPr><w:t xml:space="preserve">' . $safeText . '</w:t></w:r></w:p>';
}

$clientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($clientId === false || $clientId === null) {
    http_response_code(400);
    echo 'Invalid or missing client id.';
    exit;
}

try {
    $connection = get_database_connection();

    $clientQuery = $connection->prepare(
        'SELECT * FROM pyap_table WHERE key_id = ? LIMIT 1'
    );

    if (!$clientQuery) {
        throw new RuntimeException('Unable to prepare client query.');
    }

    $clientQuery->bind_param('i', $clientId);
    $clientQuery->execute();
    $clientResult = $clientQuery->get_result();
    $client = $clientResult ? $clientResult->fetch_assoc() : null;
    $clientQuery->close();

    if (!$client) {
        http_response_code(404);
        echo 'PYAP record not found.';
        exit;
    }

    $familyRows = [];
    $familyQuery = $connection->prepare(
        'SELECT member_name, relationship, gender, age, grade_year, isy, osy, work_labor_experience, income
         FROM pyap_family_composition_table
         WHERE pyap_id = ?
         ORDER BY id ASC'
    );

    if ($familyQuery) {
        $familyQuery->bind_param('i', $clientId);
        $familyQuery->execute();
        $familyResult = $familyQuery->get_result();

        if ($familyResult) {
            while ($row = $familyResult->fetch_assoc()) {
                $familyRows[] = $row;
            }
        }

        $familyQuery->close();
    }

    $membershipRows = [];
    $membershipQuery = $connection->prepare(
        'SELECT organization_name, position_held, year
         FROM pyap_membership_table
         WHERE pyap_id = ?
         ORDER BY id ASC'
    );

    if ($membershipQuery) {
        $membershipQuery->bind_param('i', $clientId);
        $membershipQuery->execute();
        $membershipResult = $membershipQuery->get_result();

        if ($membershipResult) {
            while ($row = $membershipResult->fetch_assoc()) {
                $membershipRows[] = $row;
            }
        }

        $membershipQuery->close();
    }

    $connection->close();
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Unable to load PYAP form data.';
    exit;
}

$fullName = build_full_name($client);
$address = trim((string) ($client['address'] ?? ''));
$currentDate = date('F d, Y');

$assistanceOptions = [
    'Educational Program (SAP)',
    'Food Subsidy',
    'Sports & Recreation Program',
    'Drug Awareness',
    'Government Outreach Program',
    'Livelihood Program',
    'Internship Outreach Program',
    'Mental Health Awareness',
    'Leadership Training',
    'Summer Job',
    'Others / Please Specify',
];

$interventionOptions = [
    'Skills Training',
    'Livelihood',
    'Others / Please Specify',
];

$assistanceSelected = normalize_multi_value_field((string) ($client['assistance_received'] ?? ''));
$interventionSelected = normalize_multi_value_field((string) ($client['intervention_needed'] ?? ''));

$docBody = '';
$docBody .= paragraph('Republic of the Philippines', 'center', false, 16, 0);
$docBody .= paragraph('CITY SOCIAL WELFARE AND DEVELOPMENT OFFICE', 'center', true, 19, 0);
$docBody .= paragraph('J. Rosales Avenue, Tandang Sora, Butuan City', 'center', false, 15, 40);
$docBody .= paragraph('PAG-ASA YOUTH ASSOCIATION OF THE PHILIPPINES', 'center', true, 18, 0);
$docBody .= paragraph('Butuan City', 'center', true, 16, 20);
$docBody .= paragraph('MEMBERSHIP INFORMATION SHEET', 'center', true, 16, 80);

$docBody .= paragraph('I. PERSONAL DATA', 'left', true, 15, 20);
$docBody .= paragraph('Name: ' . $fullName . '      Age: ' . (string) ($client['age'] ?? '') . '      Gender: ' . (string) ($client['sex'] ?? '') . '      Nationality: ' . (string) ($client['nationality'] ?? ''), 'left', false, 15, 0);
$docBody .= paragraph('Address: ' . $address . '      Status: ' . (string) ($client['civil_status'] ?? '') . '      Cell No.: ' . (string) ($client['contact_number'] ?? '') . '      Religion: ' . (string) ($client['religion'] ?? ''), 'left', false, 15, 0);
$docBody .= paragraph('Place of Birth: ' . (string) ($client['place_of_birth'] ?? '') . '      Occupation: ' . (string) ($client['occupation'] ?? '') . '      Income: ' . (string) ($client['income_data'] ?? ''), 'left', false, 15, 20);
$docBody .= paragraph('In School Youth: ' . (string) ($client['in_school_youth'] ?? '') . '      Out of School Youth: ' . (string) ($client['out_of_school_youth'] ?? '') . '      Other Disadvantage Youth: ' . (string) ($client['other_disadvantage_youth'] ?? '') . '      IP\'s: ' . (string) ($client['ips'] ?? ''), 'left', false, 15, 50);

$docBody .= paragraph('FAMILY COMPOSITION', 'left', true, 15, 20);

$docBody .= '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders><w:top w:val="single" w:sz="8"/><w:left w:val="single" w:sz="8"/><w:bottom w:val="single" w:sz="8"/><w:right w:val="single" w:sz="8"/><w:insideH w:val="single" w:sz="6"/><w:insideV w:val="single" w:sz="6"/></w:tblBorders></w:tblPr>';
$docBody .= '<w:tblGrid><w:gridCol w:w="2200"/><w:gridCol w:w="1200"/><w:gridCol w:w="600"/><w:gridCol w:w="500"/><w:gridCol w:w="900"/><w:gridCol w:w="450"/><w:gridCol w:w="450"/><w:gridCol w:w="1300"/><w:gridCol w:w="1000"/></w:tblGrid>';

$headers = ['Name', 'Relationship', 'Sex', 'Age', 'Grad/Year', 'ISY', 'OSY', 'Work/Labor Experience', 'Income'];
$docBody .= '<w:tr>';
foreach ($headers as $header) {
    $docBody .= '<w:tc><w:tcPr><w:tcW w:type="dxa" w:w="1000"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="14"/></w:rPr><w:t>' . xml_escape($header) . '</w:t></w:r></w:p></w:tc>';
}
$docBody .= '</w:tr>';

$familyVisibleRows = 6;
for ($i = 0; $i < $familyVisibleRows; $i++) {
    $row = $familyRows[$i] ?? [];
    $cells = [
        (string) ($row['member_name'] ?? ''),
        (string) ($row['relationship'] ?? ''),
        (string) ($row['gender'] ?? ''),
        (string) ($row['age'] ?? ''),
        (string) ($row['grade_year'] ?? ''),
        (string) ($row['isy'] ?? ''),
        (string) ($row['osy'] ?? ''),
        (string) ($row['work_labor_experience'] ?? ''),
        (string) ($row['income'] ?? ''),
    ];

    $docBody .= '<w:tr>';
    foreach ($cells as $cell) {
        $docBody .= '<w:tc><w:p><w:r><w:rPr><w:sz w:val="14"/></w:rPr><w:t xml:space="preserve">' . xml_escape($cell) . '</w:t></w:r></w:p></w:tc>';
    }
    $docBody .= '</w:tr>';
}
$docBody .= '</w:tbl>';

$docBody .= paragraph('School Last Attended: ' . (string) ($client['school_last_attended'] ?? '') . '      Month & Year: ' . (string) ($client['school_last_attended_month_year'] ?? ''), 'left', false, 14, 0);
$docBody .= paragraph('Skills/Interest/Hobbies: ' . (string) ($client['skills_interest_hobbies'] ?? ''), 'left', false, 14, 0);
$docBody .= paragraph('Trainings attended: ' . (string) ($client['trainings_attended'] ?? ''), 'left', false, 14, 20);

$docBody .= paragraph('II. Assistance & Intervention Received from CSWD & other Agencies:', 'left', true, 14, 0);
foreach ($assistanceOptions as $option) {
    $selected = isset($assistanceSelected[mb_strtolower($option)]);
    $docBody .= paragraph(checkbox($selected) . ' ' . $option, 'left', false, 14, 0);
}
$docBody .= paragraph('Please Specify: ' . (string) ($client['assistance_specify'] ?? ''), 'left', false, 14, 20);

$docBody .= paragraph('IV Intervention needed', 'left', true, 14, 0);
foreach ($interventionOptions as $option) {
    $selected = isset($interventionSelected[mb_strtolower($option)]);
    $docBody .= paragraph(checkbox($selected) . ' ' . $option, 'left', false, 14, 0);
}
$docBody .= paragraph('Please Specify: ' . (string) ($client['intervention_specify'] ?? ''), 'left', false, 14, 20);

$docBody .= paragraph('IV. Membership to Organization, Clubs, Society', 'left', true, 14, 10);
$docBody .= '<w:tbl><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="8"/><w:left w:val="single" w:sz="8"/><w:bottom w:val="single" w:sz="8"/><w:right w:val="single" w:sz="8"/><w:insideH w:val="single" w:sz="6"/><w:insideV w:val="single" w:sz="6"/></w:tblBorders></w:tblPr>';
$docBody .= '<w:tblGrid><w:gridCol w:w="4500"/><w:gridCol w:w="3700"/><w:gridCol w:w="1500"/></w:tblGrid>';
$docBody .= '<w:tr><w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="14"/></w:rPr><w:t>Name of Organization</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="14"/></w:rPr><w:t>Position Held, if any</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="14"/></w:rPr><w:t>Year</w:t></w:r></w:p></w:tc></w:tr>';
for ($i = 0; $i < 4; $i++) {
    $row = $membershipRows[$i] ?? [];
    $docBody .= '<w:tr><w:tc><w:p><w:r><w:rPr><w:sz w:val="14"/></w:rPr><w:t xml:space="preserve">' . xml_escape((string) ($row['organization_name'] ?? '')) . '</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:sz w:val="14"/></w:rPr><w:t xml:space="preserve">' . xml_escape((string) ($row['position_held'] ?? '')) . '</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:rPr><w:sz w:val="14"/></w:rPr><w:t xml:space="preserve">' . xml_escape((string) ($row['year'] ?? '')) . '</w:t></w:r></w:p></w:tc></w:tr>';
}
$docBody .= '</w:tbl>';

$docBody .= paragraph('Data Privacy Consent', 'center', true, 14, 0);
$docBody .= paragraph('I confirm that the information I have provided in this form is true and represents accurate information.', 'both', false, 12, 0);
$docBody .= paragraph('I understand that the data collected from this report will be processed, managed and maintained in a secure database by the City Social Welfare and Development (CSWD). Such data will used to serve as basis for research and in the development and implementation of Social Protection and Services, to promote and interest of the Out of School Youth and other Disadvantage Youth.', 'both', false, 12, 0);
$docBody .= paragraph('I authorized CSWD to manage the information, including personal data obtained from this assessment/case study and allow the processing and controlled disclosure of transfer of data to its development partners and other stakeholders in accordance with the CSWD policies and data sharing and the provisions of Republic Act. No. 10173 of the Data Policy Act (DPA) of 2012.', 'both', false, 12, 30);

$docBody .= paragraph('Name & Signature of the Youth', 'center', false, 14, 0);
$docBody .= paragraph($fullName, 'center', true, 14, 0);
$docBody .= paragraph('Youth Service Provider: ARIEL JOY J. TANSO          SWO IV / DPSD Chief: FE C. BALASON, RSW', 'left', true, 14, 0);
$docBody .= paragraph('Date: ' . $currentDate, 'left', false, 14, 10);
$docBody .= paragraph('Phone: +639562241679 / +639816016317   Email: cswdobutuan@gmail.com   Website: http://www.butuan.gov.ph   CSWDO.DPSD.F.014.REV 00', 'left', false, 12, 0);

$documentXml = <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" mc:Ignorable="w14 w15 wp14">
  <w:body>
    {$docBody}
    <w:sectPr>
      <w:pgSz w:w="12240" w:h="20160"/>
      <w:pgMar w:top="480" w:right="540" w:bottom="480" w:left="540" w:header="300" w:footer="300" w:gutter="0"/>
    </w:sectPr>
  </w:body>
</w:document>
XML;

$tempPath = tempnam(sys_get_temp_dir(), 'pyap_docx_');
if ($tempPath === false) {
    http_response_code(500);
    echo 'Unable to create temporary file.';
    exit;
}

$docxPath = $tempPath . '.docx';
@unlink($tempPath);

try {
    build_docx($documentXml, $docxPath);

    $fileName = 'pyap_membership_information_sheet_' . $clientId . '.docx';
    header('Content-Description: File Transfer');
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . (string) filesize($docxPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');

    readfile($docxPath);
} finally {
    @unlink($docxPath);
}
