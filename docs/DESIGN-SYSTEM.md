# Easy Album Orders - Design System

## Overview

This document defines the design language, UX principles, and component specifications for the Easy Album Orders WordPress plugin. The design system prioritizes clarity, professionalism, and ease of use for both photographers and their clients.

---

## Target Audiences

### Primary Users: Wedding Photographers (Admin/Backend)
- **Demographics**: Predominantly female, creative professionals
- **Technical Proficiency**: Varies widely; assume intermediate WordPress familiarity
- **Context**: Managing multiple clients, time-constrained, often working on laptops
- **Pain Points**: Complex interfaces, unclear navigation, overwhelming options
- **Needs**: Quick setup, easy client management, professional appearance

### End Users: Clients (Frontend)
- **Demographics**: Brides, mothers of brides, grooms, family members
- **Technical Proficiency**: General consumer level; assume minimal technical knowledge
- **Context**: Emotional purchase, often on mobile devices, sharing with family
- **Emotional State**: Excited but potentially overwhelmed by choices
- **Needs**: Clear guidance, visual confirmation of selections, confidence in decisions

---

## Design Philosophy

### Core Principles

#### 1. Minimal & Clean
Reduce visual noise. Every element must earn its place on the screen. White space is not wasted space—it provides breathing room and improves comprehension.

#### 2. Professional, Not Playful
This plugin handles significant purchases. The design should feel trustworthy and refined, like a high-end boutique—not a discount store or a children's app.

#### 3. Invisible Integration
The frontend must not compete with the photographer's website. Use neutral colors, subtle styling, and minimal branding so the plugin feels native to any site.

#### 4. Progressive Disclosure
Show only what's needed at each step. Reveal complexity gradually. Hide advanced options until relevant.

#### 5. Clear Affordances
Every interactive element should look interactive. Users should never wonder "can I click this?"

---

## Design Inspirations

### UI References
- **Tailwind UI Kit** (https://tailwindcss.com/plus/ui-kit) - Clean, systematic components
- **shadcn/ui** - Accessible, minimal, beautiful defaults
- **Radix UI** - Primitives-first, unstyled but well-structured
- **Linear** - Sophisticated, efficient interface design
- **Stripe Dashboard** - Clear hierarchy, excellent information density

### Anti-Patterns to Avoid
- ❌ Heavy gradients or 3D effects
- ❌ Multiple competing colors or accent tones
- ❌ Rounded corners larger than 8-12px (feels childish)
- ❌ Excessive animation or transitions
- ❌ Decorative elements without function
- ❌ Inconsistent spacing or alignment
- ❌ Low-contrast text (accessibility issue)
- ❌ Overly stylized fonts

---

## Color System

### Backend (Admin) Colors

The admin interface uses a sophisticated neutral palette with a single accent color for primary actions.

```css
:root {
    /* Primary Action */
    --eao-primary: #3858e9;        /* Blue - primary buttons, active states */
    --eao-primary-hover: #2a45c4;  /* Darker blue for hover */
    --eao-primary-light: #eef2ff;  /* Light blue for backgrounds */

    /* Status Colors */
    --eao-success: #059669;        /* Green - success, shipped */
    --eao-success-light: #d1fae5;
    --eao-warning: #d97706;        /* Amber - pending, attention */
    --eao-warning-light: #fef3c7;
    --eao-error: #dc2626;          /* Red - errors, delete */
    --eao-error-light: #fef2f2;

    /* Neutrals - Gray scale for UI */
    --eao-gray-50: #f9fafb;        /* Background */
    --eao-gray-100: #f3f4f6;       /* Subtle background */
    --eao-gray-200: #e5e7eb;       /* Borders */
    --eao-gray-300: #d1d5db;       /* Disabled */
    --eao-gray-400: #9ca3af;       /* Placeholder text */
    --eao-gray-500: #6b7280;       /* Secondary text */
    --eao-gray-600: #4b5563;       /* Primary text (light) */
    --eao-gray-700: #374151;       /* Primary text */
    --eao-gray-800: #1f2937;       /* Headings */
    --eao-gray-900: #111827;       /* High emphasis */
}
```

### Frontend (Public) Colors

The frontend uses a minimal, theme-agnostic palette that adapts to any website.

```css
:root {
    /* Neutral Base - works on any site */
    --eao-primary: #2c3e50;        /* Dark slate - headings */
    --eao-primary-light: #34495e;  /* Lighter slate */
    
    /* Single Accent - warm, inviting, not gender-specific */
    --eao-accent: #e67e22;         /* Warm amber - CTAs */
    --eao-accent-hover: #d35400;   /* Darker for hover */
    
    /* Functional Colors */
    --eao-success: #27ae60;        /* Green - confirmations */
    --eao-error: #e74c3c;          /* Red - errors */
    
    /* Text & Borders */
    --eao-text: #2c3e50;           /* Primary text */
    --eao-text-light: #7f8c8d;     /* Secondary text */
    --eao-border: #ddd;            /* Standard borders */
    --eao-border-light: #ecf0f1;   /* Subtle dividers */
    --eao-bg: #f8f9fa;             /* Light backgrounds */
}
```

### Color Usage Guidelines

| Element | Color | Rationale |
|---------|-------|-----------|
| Primary buttons | `--eao-accent` | Draws attention to key actions |
| Secondary buttons | White + border | De-emphasized but accessible |
| Success states | `--eao-success` | Universal "good" color |
| Error states | `--eao-error` | Universal "danger" color |
| Body text | `--eao-text` | High readability |
| Labels/captions | `--eao-text-light` | Reduced hierarchy |
| Backgrounds | White or `--eao-bg` | Clean, professional |

---

## Typography

### Font Stack

```css
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, 
             Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
```

**Rationale**: System fonts are fast, familiar, and render beautifully on all platforms. They don't impose a brand identity that might clash with the photographer's site.

### Type Scale

#### Backend (Admin)

| Use Case | Size | Weight | Line Height |
|----------|------|--------|-------------|
| Page Title | 24px | 600 | 1.3 |
| Section Heading | 18px | 600 | 1.4 |
| Card Heading | 16px | 600 | 1.4 |
| Body Text | 13px | 400 | 1.5 |
| Labels | 12px | 500 | 1.4 |
| Help Text | 11px | 400 | 1.5 |

#### Frontend (Public)

| Use Case | Size | Weight | Line Height |
|----------|------|--------|-------------|
| Page Title | 2.5rem | 700 | 1.2 |
| Section Heading | 1.25rem | 600 | 1.3 |
| Card Name | 0.95rem | 600 | 1.4 |
| Body Text | 1rem | 400 | 1.6 |
| Price | 1rem | 500 | 1 |
| Help Text | 0.875rem | 400 | 1.5 |

### Typography Rules

1. **Never use more than 3 font weights** on a single screen
2. **Avoid all-caps** except for small badges or labels
3. **Use sentence case** for UI labels (not Title Case)
4. **Minimum body text size**: 13px (admin), 16px (frontend)
5. **Line length**: Aim for 50-75 characters per line

---

## Spacing System

Use a consistent spacing scale based on 4px increments:

```css
--eao-space-xs: 4px;   /* Tight gaps */
--eao-space-sm: 8px;   /* Related elements */
--eao-space-md: 16px;  /* Standard padding */
--eao-space-lg: 24px;  /* Section separation */
--eao-space-xl: 32px;  /* Major sections */
--eao-space-2xl: 48px; /* Page-level spacing */
```

### Spacing Guidelines

- **Form field margin**: `16px` below each field
- **Section padding**: `24px` or `32px`
- **Card padding**: `20px` to `24px`
- **Button padding**: `8px 16px` (small), `12px 24px` (standard)
- **Related elements**: `8px` gap
- **Unrelated elements**: `24px` or more gap

---

## Border & Shadow System

### Border Radius

```css
--eao-radius-sm: 4px;    /* Small elements, tags */
--eao-radius-md: 6px;    /* Buttons, inputs */
--eao-radius-lg: 8px;    /* Cards, modals */
--eao-radius-xl: 12px;   /* Large cards */
--eao-radius-full: 9999px; /* Pills, avatars */
```

**Note**: Avoid radii larger than 12px—they can look unprofessional or childish.

### Shadows

```css
--eao-shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);   /* Subtle lift */
--eao-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1); /* Cards */
--eao-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1); /* Modals, dropdowns */
```

### Borders

```css
--eao-border: 1px solid var(--eao-gray-200);  /* Standard */
```

Use borders sparingly. Prefer subtle backgrounds or shadows to separate content when possible.

---

## Backend (Admin) Design Guidelines

### Navigation & Information Architecture

```
📁 Client Albums (parent menu)
├── All Client Albums
├── Add New
└── Album Options (global settings)

📦 Album Orders (standalone menu)
└── All orders with filters
```

### Admin Page Structure

1. **Page Title**: Top-left, clear hierarchy
2. **Tab Navigation**: Horizontal tabs below title (when applicable)
3. **Content Area**: White card(s) with consistent padding
4. **Actions**: Primary button in header or footer

### Form Patterns

#### Single-Column Forms
- Use for simple data entry
- Maximum width: 600px
- Labels above inputs

#### Two-Column Forms
- Use when grouping related fields makes sense
- Break to single column on mobile

#### Repeater Fields
- Collapsible items with clear expand/collapse affordance
- Drag handle for reordering
- Delete confirmation before removal
- Clear "Add New" button at bottom

### Meta Box Guidelines

- Keep meta boxes focused (one concern per box)
- Use consistent internal spacing
- Group related fields visually
- Place most-used fields at top

### Empty States

Always design empty states. They should:
- Explain what belongs here
- Provide a clear action to get started
- Use friendly, helpful language (not error language)

### Loading States

- Use skeleton loaders for content areas
- Use spinners for actions in progress
- Disable buttons during submission
- Show progress for multi-step operations

---

## Frontend (Public) Design Guidelines

### Layout Structure

```
┌─────────────────────────────────────────────┐
│               Header (optional)             │
├─────────────────────────────────────────────┤
│            Welcome Video (optional)         │
├───────────────────────────────┬─────────────┤
│                               │             │
│        Order Form             │    Cart     │
│        (main column)          │   Widget    │
│                               │  (sticky)   │
│                               │             │
├───────────────────────────────┴─────────────┤
│              Order History (if any)         │
└─────────────────────────────────────────────┘
```

### Form Section Flow

The order form should follow a logical progression:

1. **Album Name** - Personal identifier
2. **Design Selection** - The "what" (with PDF preview)
3. **Material Selection** - Visual material images
4. **Color Selection** - Swatches with preview images
5. **Size Selection** - Clear dimension labels
6. **Engraving** (conditional) - Only if material allows
7. **Price Summary** - Transparent breakdown
8. **Add to Cart** - Clear CTA

### Selection Cards

Used for designs, materials, sizes, and colors.

```
┌──────────────────────────────┐
│                              │
│         [Image]              │
│                              │
├──────────────────────────────┤
│ Material Name                │
│ +$XX.XX (or "Included")      │
└──────────────────────────────┘
```

**States**:
- **Default**: Subtle border, white background
- **Hover**: Slight elevation, darker border
- **Selected**: Accent border, subtle accent background

### Color Swatches

Display colors with both swatch and preview image when available:

```
┌──────────────────────────────┐
│                              │
│    [Preview Image]           │
│                   ┌────┐     │
│                   │ ○  │     │  ← Color swatch overlay
│                   └────┘     │
├──────────────────────────────┤
│       Color Name             │
└──────────────────────────────┘
```

### Cart Widget

Sticky sidebar on desktop, collapses on mobile.

```
┌──────────────────────────────┐
│ 🛒 Your Albums (2)           │
├──────────────────────────────┤
│ Album Name                $X │
│ Italian Leather · 10x10      │
│ [Edit] [Remove]              │
├──────────────────────────────┤
│ Album Name 2              $X │
│ Linen · 8x10                 │
│ [Edit] [Remove]              │
├──────────────────────────────┤
│ Total                   $XXX │
│                              │
│ [    Checkout    ]           │
└──────────────────────────────┘
```

### Price Display

Always show transparent pricing:

```
Base Price                 $500.00
Material (Italian Leather)  +$100.00
Size (12x12)                +$50.00
Engraving (Foil Stamp)      +$99.00
─────────────────────────────────
Subtotal                   $749.00
Album Credit              -$250.00  ← Green, if applicable
─────────────────────────────────
Total                      $499.00
```

### Mobile Considerations

- Single-column layout below 992px
- Cart moves below form on mobile
- Touch targets minimum 44x44px
- Selection cards: 2 columns on tablet, 1 on phone
- Sticky cart button at bottom of screen (mobile)

---

## UX Guidelines

### User Flow Clarity

#### 1. Progress Indication
Users should always know:
- Where they are in the process
- What they've selected
- What happens next

#### 2. Selection Confirmation
When a user selects an option:
- Visual state change (border, background)
- Price updates immediately
- Smooth transition (150-200ms)

#### 3. Error Prevention
- Validate inline, not just on submit
- Disable invalid combinations proactively
- Show character counters for engraving

#### 4. Error Recovery
- Clear error messages explaining the problem
- Point to the solution, not just the problem
- Don't clear valid form data on error

### Micro-Interactions

**Transitions**: `150ms ease` for most interactions

**Button hover**: Slight elevation + color darken

**Card selection**: Border color change + subtle scale (1.02)

**Form focus**: Border color change + focus ring

### Feedback Patterns

| Action | Feedback |
|--------|----------|
| Add to Cart | Success message + cart update animation |
| Remove from Cart | Confirmation dialog first |
| Checkout | Loading state → success modal |
| Form Error | Inline error message + scroll to first error |
| Save Settings (admin) | Toast notification |

### Copy Guidelines

#### Tone
- Professional but warm
- Helpful, not commanding
- Confident, not pushy

#### Button Labels
- Use verbs: "Add to Cart", "Submit Order", "Save Changes"
- Be specific: "Add Album to Cart" not just "Submit"
- Avoid generic: "OK", "Submit", "Continue"

#### Error Messages
- ✅ "Please enter your phone number"
- ❌ "Error: Phone field is required"

#### Empty States
- ✅ "No albums in your cart yet. Configure your first album below!"
- ❌ "Cart is empty"

---

## Component Reference

### Buttons

| Type | Use Case | Appearance |
|------|----------|------------|
| Primary | Main action | Solid accent color |
| Secondary | Alternative action | White + border |
| Ghost | Tertiary action | No border, text only |
| Danger | Destructive action | Red variant |

### Form Inputs

| State | Appearance |
|-------|------------|
| Default | Gray border, white background |
| Focus | Accent border + focus ring |
| Error | Red border + error message below |
| Disabled | Gray background, lower opacity |

### Cards

| Type | Use Case |
|------|----------|
| Selection Card | Choosing materials, designs, etc. |
| Info Card | Displaying read-only information |
| Settings Card | Grouping form fields |
| Cart Item Card | Items in cart |

### Status Badges

| Status | Color | Use |
|--------|-------|-----|
| Submitted | Yellow/Amber | Album in cart |
| Ordered | Blue | Checkout complete |
| Shipped | Green | Order fulfilled |

### Modals

- Max-width: 480px (small), 640px (medium), 800px (large)
- Semi-transparent backdrop with blur
- Clear close button (X) in top-right
- Footer with action buttons aligned right

---

## Accessibility Requirements

### Color Contrast
- Text: Minimum 4.5:1 ratio (WCAG AA)
- Large text: Minimum 3:1 ratio
- Interactive elements: Clear focus states

### Keyboard Navigation
- All interactive elements focusable
- Logical tab order
- Visible focus indicators
- Escape closes modals

### Screen Readers
- Proper heading hierarchy (h1 → h2 → h3)
- Descriptive button labels
- Form labels associated with inputs
- ARIA labels where needed
- Alt text for all images

### Touch Targets
- Minimum 44x44px for all interactive elements
- Adequate spacing between touch targets

---

## WordPress Integration

### Admin UI Consistency

While creating a custom design, maintain harmony with WordPress admin:

- Use similar grays for backgrounds
- Keep button styles recognizable
- Don't fight WordPress patterns for common elements
- Ensure compatibility with admin color schemes

### Frontend Theme Compatibility

- Use CSS custom properties for easy theme overrides
- Scope all styles with `.eao-` prefix
- Don't use `!important` unless absolutely necessary
- Test with popular themes (Astra, GeneratePress, Divi, Elementor)
- Support dark mode if possible (via `prefers-color-scheme`)

### Asset Loading

- Only load CSS/JS on pages that need them
- Minify production assets
- Use WordPress enqueue system
- Defer non-critical scripts

---

## Implementation Notes

### CSS Architecture

```scss
// File structure
assets/css/
├── admin.css        // All admin styles
├── public.css       // All frontend styles
└── (optional) 
    ├── components/  // Reusable component styles
    └── utilities/   // Utility classes
```

### Naming Convention (BEM)

```css
/* Block */
.eao-card { }

/* Element */
.eao-card__header { }
.eao-card__body { }
.eao-card__footer { }

/* Modifier */
.eao-card--selected { }
.eao-card--disabled { }
```

### Responsive Breakpoints

```css
/* Mobile first approach */
@media (min-width: 480px) { }  /* Small phones */
@media (min-width: 768px) { }  /* Tablets */
@media (min-width: 992px) { }  /* Desktop */
@media (min-width: 1200px) { } /* Large desktop */
```

---

## Testing Checklist

### Visual Testing
- [ ] All color combinations meet contrast requirements
- [ ] Components render correctly at all breakpoints
- [ ] Hover/focus states visible and consistent
- [ ] Empty states designed and implemented
- [ ] Loading states designed and implemented
- [ ] Error states designed and implemented

### Functional Testing
- [ ] All forms validate correctly
- [ ] All buttons have appropriate feedback
- [ ] Cart updates correctly
- [ ] Prices calculate correctly
- [ ] PDF viewer works on all browsers

### Accessibility Testing
- [ ] Screen reader navigation works
- [ ] Keyboard navigation works
- [ ] Focus order is logical
- [ ] No keyboard traps
- [ ] All images have alt text

### Cross-Browser Testing
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] iOS Safari
- [ ] Chrome for Android

### Theme Compatibility Testing
- [ ] Default WordPress themes
- [ ] Astra
- [ ] GeneratePress
- [ ] OceanWP
- [ ] Divi
- [ ] Elementor Hello

---

## Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | TBD | Initial design system documentation |

---

*This is a living document. Update it as the design evolves.*

