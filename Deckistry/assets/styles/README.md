# Deckistry SASS Architecture

## 📁 7-1 Architecture Pattern

This project uses the industry-standard **7-1 SASS architecture pattern**, which consists of 7 folders and 1 main import file (`app.scss`).

```
assets/styles/
├── abstracts/          # Variables, mixins, functions
│   ├── _variables.scss
│   ├── _mixins.scss
│   └── _functions.scss
├── base/              # Base styles, resets
│   ├── _reset.scss
│   ├── _typography.scss
│   └── _utilities.scss
├── components/        # Reusable UI components
│   ├── _buttons.scss
│   ├── _cards.scss
│   ├── _forms.scss
│   ├── _navigation.scss
│   ├── _modals.scss
│   ├── _badges.scss
│   ├── _alerts.scss
│   └── _tables.scss
├── layout/           # Layout structure
│   ├── _grid.scss
│   ├── _header.scss
│   ├── _footer.scss
│   └── _sidebar.scss
├── pages/            # Page-specific styles
│   ├── _home.scss
│   ├── _deck.scss
│   ├── _collection.scss
│   └── _search.scss
├── themes/           # Theme overrides
│   └── (future: dark mode, etc.)
├── vendors/          # Third-party libraries
│   └── (normalize, etc.)
└── app.scss          # Main import file
```

## 🎨 Magic: The Gathering Theme

The SASS architecture includes Magic: The Gathering specific variables and components:

### Color Palette
- **White**: `$mtg-white: #FFFBD5`
- **Blue**: `$mtg-blue: #0E68AB`
- **Black**: `$mtg-black: #150B00`
- **Red**: `$mtg-red: #D3202A`
- **Green**: `$mtg-green: #00733E`

### Rarity Colors
- **Common**: `$rarity-common: #1A1A1A`
- **Uncommon**: `$rarity-uncommon: #707070`
- **Rare**: `$rarity-rare: #A38F31`
- **Mythic**: `$rarity-mythic: #BF4427`

### Card Dimensions
- Width: `$card-width: 250px`
- Height: `$card-height: 350px`
- Aspect Ratio: 1.4 (350/250)

## 📚 Folder Descriptions

### 1. **abstracts/**
Contains SASS tools and helpers that don't output CSS directly.

- **_variables.scss**: Colors, typography, spacing, breakpoints, shadows, MTG-specific variables
- **_mixins.scss**: Reusable code blocks (responsive, flexbox, animations, Magic card layouts)
- **_functions.scss**: SASS functions (spacing, color manipulation, unit conversion)

### 2. **base/**
Foundational styles that apply globally.

- **_reset.scss**: CSS reset/normalize
- **_typography.scss**: Font definitions, headings, text utilities
- **_utilities.scss**: Helper classes (display, flexbox, spacing, shadows)

### 3. **components/**
Reusable UI components following BEM-like naming.

- **_buttons.scss**: Button variants (primary, secondary, mana colors)
- **_cards.scss**: Magic card display, deck cards, collection cards
- **_forms.scss**: Form inputs, validation, checkboxes, switches
- **_navigation.scss**: Navbar, sidebar, breadcrumbs, tabs, pagination
- **_modals.scss**: Modal dialogs, dropdowns, tooltips, popovers
- **_badges.scss**: Badge variants, mana badges, rarity badges, quantity badges
- **_alerts.scss**: Alert messages, toasts, progress bars, spinners
- **_tables.scss**: Table styles, card tables, deck tables, sortable tables

### 4. **layout/**
Major layout components and grid system.

- **_grid.scss**: Container, rows, columns, responsive grid system
- **_header.scss**: Site header, navigation bar, search, notifications
- **_footer.scss**: Site footer with links, newsletter, social media
- **_sidebar.scss**: Sidebar navigation with filters (color, rarity, etc.)

### 5. **pages/**
Page-specific styles that don't fit elsewhere.

- **_home.scss**: Landing page (hero, features, CTA, testimonials)
- **_deck.scss**: Deck builder interface (search panel, deck list, stats)
- **_collection.scss**: Collection management (grid/list views, filters, bulk actions)
- **_search.scss**: Card search page (filters, results, pagination)

### 6. **themes/**
Theme variations (currently empty, ready for dark mode).

### 7. **vendors/**
Third-party library styles (currently empty).

## 🚀 Usage

### Importing the Main Stylesheet

In your Symfony template (`base.html.twig`):

```twig
{# Using Symfony Asset Mapper #}
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
```

Or if using webpack/encore:

```twig
{{ encore_entry_link_tags('app') }}
```

### Using SASS Variables

```scss
// In your custom SCSS file
.my-component {
  color: $accent;
  padding: spacing(4);
  border-radius: $border-radius;
  box-shadow: $shadow-md;
}
```

### Using Mixins

```scss
// Responsive design
.my-element {
  @include respond-below(md) {
    display: none;
  }
}

// Flexbox centering
.centered-content {
  @include flex-center;
}

// Magic card layout
.card-wrapper {
  @include magic-card-layout;
}

// Button variant
.custom-button {
  @include button-variant($mtg-blue, $white);
}
```

### Using Functions

```scss
.element {
  margin: spacing(5); // Uses spacing scale
  padding: rem(20); // Converts px to rem
  color: tint($accent, 20%); // Lightens color
}
```

## 🎯 Component Classes

### Buttons
```html
<button class="btn btn-primary">Primary</button>
<button class="btn btn-mana btn-blue">Blue Mana</button>
<button class="btn btn-lg">Large Button</button>
```

### Cards
```html
<div class="magic-card">
  <img src="card-image.jpg" alt="Card">
  <div class="card-info">
    <h3>Card Name</h3>
  </div>
</div>

<div class="deck-card">
  <!-- Deck preview -->
</div>
```

### Badges
```html
<span class="badge badge-primary">Primary</span>
<span class="badge badge-mana-blue">U</span>
<span class="badge badge-rare">Rare</span>
<span class="badge-quantity">4</span>
```

### Forms
```html
<div class="form-group">
  <label class="form-label">Card Name</label>
  <input type="text" class="form-control" placeholder="Search...">
</div>
```

### Grid System
```html
<div class="container">
  <div class="row">
    <div class="col-md-6">Half width on medium+</div>
    <div class="col-md-6">Half width on medium+</div>
  </div>
</div>
```

## 📐 Breakpoints

```scss
$breakpoints: (
  sm: 576px,   // Small devices
  md: 768px,   // Tablets
  lg: 992px,   // Desktops
  xl: 1200px,  // Large desktops
  xxl: 1400px  // Extra large
);
```

Use with mixins:
```scss
@include respond-above(md) { /* styles */ }
@include respond-below(lg) { /* styles */ }
@include respond-between(md, lg) { /* styles */ }
```

## 🎨 Spacing Scale

The spacing scale follows an 8px base:

```scss
spacing(1)  // 4px
spacing(2)  // 8px
spacing(3)  // 12px
spacing(4)  // 16px
spacing(5)  // 20px
spacing(6)  // 24px
spacing(8)  // 32px
spacing(10) // 40px
spacing(12) // 48px
spacing(15) // 60px
spacing(20) // 80px
```

## 🔧 Customization

### Adding New Colors
Edit `abstracts/_variables.scss`:

```scss
$custom-color: #FF5733;
```

### Creating New Components
1. Create `components/_your-component.scss`
2. Import in `app.scss`:
```scss
@import 'components/your-component';
```

### Adding Page-Specific Styles
1. Create `pages/_your-page.scss`
2. Import in `app.scss`:
```scss
@import 'pages/your-page';
```

## 🛠️ Development Tips

1. **Use BEM Naming**: `.block__element--modifier`
2. **Prefer Variables**: Use `$accent` instead of hardcoded colors
3. **Use Spacing Function**: `spacing(4)` instead of `16px`
4. **Mobile First**: Write base styles for mobile, then use `@include respond-above()`
5. **Component Reusability**: Keep components generic and flexible
6. **Avoid Deep Nesting**: Maximum 3-4 levels deep

## 📝 File Naming Convention

- Partials start with underscore: `_buttons.scss`
- Use kebab-case: `_button-variants.scss`
- One component per file
- Group related components in folders if needed

## 🔍 Finding Styles

- **Looking for colors?** → `abstracts/_variables.scss`
- **Need a responsive mixin?** → `abstracts/_mixins.scss`
- **Want to style a button?** → `components/_buttons.scss`
- **Building a layout?** → `layout/_grid.scss`
- **Page-specific styles?** → `pages/`

## 📦 Compilation

### Using Symfony Asset Mapper
Asset Mapper automatically compiles SASS to CSS. No configuration needed.

### Using Symfony Encore
```javascript
// webpack.config.js
Encore
  .addStyleEntry('app', './assets/styles/app.scss')
  .enableSassLoader()
;
```

Then run:
```bash
npm run dev        # Development
npm run watch      # Watch mode
npm run build      # Production
```

## 🎯 Best Practices

1. **Keep it DRY**: Use variables, mixins, and functions
2. **Mobile First**: Design for smallest screens first
3. **Semantic Classes**: Use meaningful names (`.card-grid` not `.flex-wrap`)
4. **Consistent Spacing**: Use the spacing scale
5. **Document Complex Code**: Add comments for tricky selectors
6. **Test Responsiveness**: Check all breakpoints
7. **Optimize for Performance**: Minimize nesting, avoid expensive selectors

## 🚨 Common Issues

### SASS Not Compiling
- Check file paths in imports
- Ensure SASS loader is installed
- Verify no syntax errors

### Styles Not Applying
- Check specificity
- Verify correct class names
- Check browser cache

### Responsive Issues
- Test all breakpoints
- Use browser dev tools
- Check viewport meta tag

## 📚 Resources

- [SASS Documentation](https://sass-lang.com/documentation)
- [7-1 Pattern](https://sass-guidelin.es/#architecture)
- [BEM Methodology](http://getbem.com/)
- [Scryfall API](https://scryfall.com/docs/api)

## 🤝 Contributing

When adding new styles:
1. Follow the existing structure
2. Use appropriate folder (components, pages, etc.)
3. Add variables for reusable values
4. Test across all breakpoints
5. Document complex code

---

**Built with ❤️ for Magic: The Gathering deck builders**
