# Custom SVG Icons

This folder is for storing custom SVG icons that you want to use in the application.

## Usage

1. **Upload your SVG files** to this directory
2. **Name them descriptively** (e.g., `custom-pest.svg`, `olive-branch.svg`)
3. **Use them in components** with the methods shown below

## Method 1: Direct Import (Recommended)

```vue
<template>
  <div>
    <!-- Using as img src -->
    <img :src="customIcon" alt="Custom Icon" width="24" height="24" />
    
    <!-- Using in v-icon with custom component -->
    <v-icon>
      <img :src="customIcon" alt="Custom Icon" width="20" height="20" />
    </v-icon>
  </div>
</template>

<script setup>
import customIcon from '../assets/icons/your-icon.svg'
</script>
```

## Method 2: Add to Vuetify Icon Aliases

Add your custom SVG to the Vuetify configuration in `src/plugins/vuetify.ts`:

```javascript
// In vuetify.ts
import customPestIcon from '../assets/icons/custom-pest.svg'

// Add to aliases
aliases: {
  ...aliases,
  customPest: customPestIcon,
}
```

Then use it like:
```vue
<v-icon icon="$customPest" />
```

## Method 3: Inline SVG Component

Create a Vue component for complex SVGs:

```vue
<!-- CustomIcon.vue -->
<template>
  <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
    <!-- Your SVG content here -->
  </svg>
</template>
```

## File Requirements

- **Format**: SVG files only
- **Size**: Optimize for web (keep file size small)
- **Colors**: Use `currentColor` for fill/stroke to inherit text color
- **ViewBox**: Ensure proper viewBox is set for scaling

## Examples

- `pest-detector.svg` - Custom pest detection icon
- `olive-tree.svg` - Stylized olive tree
- `spray-bottle.svg` - Treatment application icon
- `weather-station.svg` - Weather monitoring icon