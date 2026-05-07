# Codex Prompt: Update SCSR Print Form Header (Match Reference Exactly)

You are updating the **SCSR print form header** in my existing codebase.

## Objective
Recreate the header so it visually matches the provided reference image as closely as possible.

## Important constraints
1. **Do not generate any image.** Use the existing assets/files in the project.
2. Use the attached reference image only as the visual target.
3. We are updating **header only** (footer will be handled later).
4. Keep the implementation print-friendly and consistent across browsers.

## Required visual details
- The layout must match the reference structure:
  - Left: city seal/logo.
  - Right: stacked text block.
- Logo size must be exactly **2.43 cm converted to pixels**.
  - Use standard CSS print conversion: `1in = 96px`, `1in = 2.54cm`.
  - Therefore: `2.43cm ≈ 91.84px`.
  - Implement as `width: 91.84px;` (or `92px` if rounding is necessary, but keep proportion accurate).
- Text size should be **at least 13px**.
- Add a horizontal line under the header with a **dark-blue to blue gradient** (left to right).
- Keep spacing, alignment, and typography close to the reference image.

## Technical expectations
- Find the actual SCSR print form template and styles in the repo.
- Update only files relevant to the header.
- Avoid breaking other forms/pages.
- Prefer reusable CSS classes if the project already has shared print styles.

## Output format
1. Show which files were changed.
2. Provide the exact CSS/HTML changes.
3. Briefly explain how each requirement was implemented (logo px conversion, font size, gradient line, alignment).
4. If any existing CSS conflicts with these requirements, state the conflict and how you resolved it.
