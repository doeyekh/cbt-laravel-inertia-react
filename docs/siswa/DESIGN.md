---
name: Academic Pulse
colors:
  surface: '#f8f9ff'
  surface-dim: '#ccdbf3'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e6eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d5e3fc'
  on-surface: '#0d1c2e'
  on-surface-variant: '#434655'
  inverse-surface: '#233144'
  inverse-on-surface: '#eaf1ff'
  outline: '#737686'
  outline-variant: '#c3c6d7'
  surface-tint: '#0053db'
  primary: '#004ac6'
  on-primary: '#ffffff'
  primary-container: '#2563eb'
  on-primary-container: '#eeefff'
  inverse-primary: '#b4c5ff'
  secondary: '#545f73'
  on-secondary: '#ffffff'
  secondary-container: '#d5e0f8'
  on-secondary-container: '#586377'
  tertiary: '#006242'
  on-tertiary: '#ffffff'
  tertiary-container: '#007d55'
  on-tertiary-container: '#bdffdb'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#dbe1ff'
  primary-fixed-dim: '#b4c5ff'
  on-primary-fixed: '#00174b'
  on-primary-fixed-variant: '#003ea8'
  secondary-fixed: '#d8e3fb'
  secondary-fixed-dim: '#bcc7de'
  on-secondary-fixed: '#111c2d'
  on-secondary-fixed-variant: '#3c475a'
  tertiary-fixed: '#6ffbbe'
  tertiary-fixed-dim: '#4edea3'
  on-tertiary-fixed: '#002113'
  on-tertiary-fixed-variant: '#005236'
  background: '#f8f9ff'
  on-background: '#0d1c2e'
  surface-variant: '#d5e3fc'
typography:
  headline-xl:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 44px
    letterSpacing: -0.02em
  headline-xl-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 34px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 32px
    letterSpacing: -0.01em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 28px
    letterSpacing: -0.01em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  body-sm:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
  label-lg:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.01em
  label-md:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
    letterSpacing: 0.01em
  label-sm:
    fontFamily: Inter
    fontSize: 11px
    fontWeight: '600'
    lineHeight: 14px
    letterSpacing: 0.04em
  mono-timer:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '700'
    lineHeight: 24px
    letterSpacing: 0.05em
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-sm: 1rem
  margin: 2rem
  margin-mobile: 1rem
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2rem
  space-2xl: 3rem
---

## Brand & Style

This design system delivers an organized, high-stakes yet reassuring digital examination environment tailored for high school students. The interface prioritizes clarity, cognitive ease, and academic confidence, eliminating visual noise so candidates can focus wholly on assessment readiness and performance. 

The aesthetic is Modern Clean EdTech—a fusion of structured utility and approachable polish. It leverages subtle architectural container lines, generous white space, balanced contrast ratios, and clear spatial hierarchy. While exam portals historically lean punitive or clinical, this design system fosters an empowering, calm atmosphere through rounded geometric surfaces, crisp borders, and unambiguous status indicators.

## Colors

The color palette centers on a disciplined academic blue paired with deep foundational neutrals and contextual semantic accents:

- **Primary (`#2563EB` - Royal Blue):** Anchors primary actions, active test triggers, progress bars, and high-priority interactive states.
- **Secondary (`#1E293B` - Deep Navy):** Provides high-contrast anchor tones for primary headers, navigation frames, and critical metric counters.
- **Tertiary (`#10B981` - Emerald Green):** Signals operational readiness, successful token validations, completed submissions, and optimal system diagnostics.
- **Neutral (`#475569` - Slate):** Governs secondary labels, metadata, supporting microcopy, and neutral border states.
- **Warning Accent (`#F59E0B` - Amber):** Highlights impending test start windows, active timer countdown thresholds, and partial device diagnostics.

### Surface Color Logic
- **App Canvas:** `#F8FAFC` provides a calm, glare-free background.
- **Surface Elevation 1 (Cards & Modules):** `#FFFFFF`.
- **Subtle Surface (Inner Wells & Diagnostic Modules):** `#F1F5F9`.
- **Card Border Stroke:** `#E2E8F0` at 1px for crisp structural definition.

## Typography

The type system blends the energetic, approachable geometric structure of **Plus Jakarta Sans** for headlines and display contexts with the technical legibility of **Inter** for core reading, status metadata, and data inputs. 

- **Display & Headings:** Rendered in Plus Jakarta Sans with subtle negative tracking (`-0.01em` to `-0.02em`) to ground candidate names, module headers, and test titles.
- **Body & Data:** Powered by Inter to deliver unambiguous character differentiation (vital for instructions, token inputs, and examination rules).
- **Tabular Figures:** Timers, test tokens, and diagnostic metric figures must enforce `font-feature-settings: 'tnum'` to prevent shifting layouts during live countdowns.

## Layout & Spacing

The portal utilizes a hybrid layout with a fixed, collapsed-capable persistent sidebar and a 12-column responsive fluid grid inside the primary viewport container (max-width `1440px`).

### Breakpoints & Grid Composition
- **Desktop (1024px+):** 12 columns, 1.5rem (`24px`) gutters, 2rem (`32px`) margins. Sidebar docked to the left (260px wide). Main content spans a two-tier layout: primary assessment cards span 8 columns, right-hand diagnostic check and readiness metrics occupy 4 columns.
- **Tablet (768px - 1023px):** 8 columns, 1rem (`16px`) gutters, 1.5rem (`24px`) margins. The right utility column wraps below the active test schedule.
- **Mobile (< 768px):** 4 columns, 1rem (`16px`) gutters, 1rem (`16px`) canvas margins. All multi-column cards stack vertically into a single column flow.

### Spacing Discipline
Maintain a strict 8pt vertical rhythm. Nested items inside cards use `space-sm` (`8px`) or `space-md` (`16px`). Section groupings utilize `space-xl` (`32px`) to maintain scannability during stressful pre-exam states.

## Elevation & Depth

This system avoids heavy shadows, adopting crisp architectural layering, delicate low-opacity perimeter strokes, and restrained ambient elevation to establish hierarchy:

- **Level 0 (Canvas):** Flat base surface (`#F8FAFC`).
- **Level 1 (Default Cards & Panels):** Solid white (`#FFFFFF`) framed with a 1px border of `#E2E8F0` and an ambient shadow: `0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05)`.
- **Level 2 (Active Examination / Focus Cards):** Highlighted card surfaces feature a reinforced border (`#CBD5E1`) and a soft elevation shadow: `0 4px 6px -1px rgba(37, 99, 235, 0.06), 0 2px 4px -2px rgba(15, 23, 42, 0.05)`.
- **Level 3 (Modals / Token Entry Overlays):** Surface `#FFFFFF`, border `#CBD5E1`, shadow `0 20px 25px -5px rgba(15, 23, 42, 0.1), 0 8px 10px -6px rgba(15, 23, 42, 0.08)`.
- **Inner Wells (Diagnostics / Counters):** Inset surfaces using `#F1F5F9` with a subtle inner border `#E2E8F0`, without drop shadows.

## Shapes

The design system maintains a modern, accessible geometry with a default base radius of `0.5rem` (`8px`), expanding to `1rem` (`16px`) for major contextual cards (`rounded-xl` container language).

- **Outer Containers & Main Examination Cards:** `1rem` (`16px` / `rounded-xl`).
- **Buttons, Form Inputs, & Diagnostic Tiles:** `0.5rem` (`8px` / `rounded-md`).
- **Badges, Token Code Chunks, & Status Pills:** Full circular curvature (`9999px` / `rounded-full`).

## Components

### Buttons
- **Primary:** Royal Blue (`#2563EB`) background, white text, 0.5rem radius, height `40px` (or `48px` for "Start Examination"), medium weight Inter. Hover shifts to `#1D4ED8`. Active state uses `scale(0.99)`.
- **Secondary / Ghost:** Transparent background, 1px border in `#CBD5E1`, text in `#1E293B`. Hover fills with `#F1F5F9`.
- **Disabled State:** Solid `#E2E8F0` with text `#94A3B8`, cursor `not-allowed`.

### Status Badges & Pills
- Pill-shaped (`rounded-full`), padded `4px 12px`, typography `label-sm` (uppercase, `0.04em` tracking).
- **Ready / Passed:** `#ECFDF5` background with `#047857` text and `#A7F3D0` border.
- **In-Progress / Upcoming:** `#EFF6FF` background with `#1D4ED8` text and `#BFDBFE` border.
- **Action Required / Warning:** `#FFFBEB` background with `#B45309` text and `#FDE68A` border.

### Examination Schedule Cards
- Encased in a `rounded-xl` container with white background and 1px `#E2E8F0` border.
- Features a prominent subject tag, test duration badge, and scheduled time window.
- Includes a live countdown block utilizing tabular numbers (`mono-timer`).
- Contains a protected "Input Token" trigger: expands into a segmented 6-character monospace input field followed by a high-contrast primary CTA ("Unlock & Begin Test").

### Device Readiness Check Metrics
- A modular diagnostic card containing 4 standardized parameter rows: Webcam/Proctor Link, Microphone, Network Latency (< 100ms), and Secure Browser Lock.
- Each row contains an icon, title, current metric reading (e.g., "18ms - Optimal"), and an automated status check icon (Emerald check or Amber alert).

### Form Controls & Token Input
- **Standard Input:** Height `42px`, 1px border `#CBD5E1`, background `#FFFFFF`, text `#1E293B`, focus ring 2px Royal Blue with 2px offset.
- **Examination Token Field:** 6-cell partitioned character boxes, uppercase, bold monospace glyphs, center-aligned, with active border `#2563EB` and light blue focus halo.

### Recent Test Results Summaries
- Tabular row list items with light gray divider borders (`#F1F5F9`).
- Displays subject title, completion date, score pill (e.g., `94/100`), status indicator ("Verified"), and a tertiary link ("Review Performance Details").