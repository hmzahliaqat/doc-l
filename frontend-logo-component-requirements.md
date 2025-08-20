# Logo Component Requirements for Vue.js Frontend

## Overview
This document outlines the requirements for implementing a Vue.js component that consumes the `/settings/logo` API endpoint to display the application logo in the frontend.

## API Endpoint Details
- **URL**: `/settings/logo`
- **Method**: GET
- **Controller**: `SuperAdminController@getLogo`
- **Authentication**: None (publicly accessible)
- **Response Format**:
  ```json
  {
    "logo_url": "https://example.com/storage/logos/1234567890_logo.png",
    "alt_text": "Clickesignature"
  }
  ```
  - If no logo is set, the endpoint returns a default logo path: `/logo-dark.png`
  - The `alt_text` is derived from the application name or defaults to "Clickesignature"

## Component Requirements

### 1. Logo Component (AppLogo.vue)

#### Props
- `size`: String - Size of the logo (small, medium, large) - Optional, defaults to "medium"
- `className`: String - Additional CSS classes to apply to the logo - Optional
- `fallbackLogo`: String - URL to a fallback logo if the API request fails - Optional

#### Data Properties
- `logoUrl`: String - URL of the logo image
- `logoAlt`: String - Alt text for the logo image
- `isLoading`: Boolean - Loading state indicator
- `hasError`: Boolean - Error state indicator

#### Methods
- `fetchLogo()`: Async method to fetch the logo data from the API
- `handleError()`: Method to handle API request errors

#### Lifecycle Hooks
- `created()`: Call `fetchLogo()` when the component is created

#### Template Structure
- Container div with appropriate classes
- Loading indicator (shown when `isLoading` is true)
- Error state (shown when `hasError` is true)
- Image element with:
  - `src` bound to `logoUrl`
  - `alt` bound to `logoAlt`
  - Dynamic classes based on `size` prop
  - Additional classes from `className` prop

### 2. API Service (logoService.js)

#### Methods
- `getLogo()`: Async function that fetches logo data from the API
  - Returns a promise that resolves to the logo data
  - Handles errors appropriately

## Implementation Example

### Logo Service (logoService.js)
```javascript
/**
 * Service for fetching logo data from the API
 */
export default {
  /**
   * Fetch the application logo data
   * @param {string} apiUrl - Base API URL (default: process.env.VUE_APP_API_URL or http://localhost:8000/api)
   * @returns {Promise<Object>} - Promise resolving to logo data { logo_url, alt_text }
   */
  async getLogo(apiUrl = process.env.VUE_APP_API_URL || 'http://localhost:8000/api') {
    try {
      const url = `${apiUrl}/settings/logo`;
      
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        }
      });
      
      if (!response.ok) {
        throw new Error(`Failed to fetch logo: ${response.status} ${response.statusText}`);
      }
      
      return await response.json();
    } catch (error) {
      console.error('Error fetching logo:', error);
      throw error;
    }
  }
};
```

### Logo Component (AppLogo.vue)
```vue
<template>
  <div class="app-logo" :class="{ 'is-loading': isLoading, 'has-error': hasError }">
    <!-- Loading state -->
    <div v-if="isLoading" class="app-logo__loading">
      <span class="loading-spinner"></span>
    </div>
    
    <!-- Error state -->
    <div v-else-if="hasError" class="app-logo__error">
      <img 
        :src="fallbackLogo || '/logo-dark.png'" 
        alt="Application Logo" 
        :class="['app-logo__image', `app-logo__image--${size}`, className]"
      />
    </div>
    
    <!-- Logo -->
    <img 
      v-else
      :src="logoUrl" 
      :alt="logoAlt" 
      :class="['app-logo__image', `app-logo__image--${size}`, className]"
    />
  </div>
</template>

<script>
import logoService from '@/services/logoService';

export default {
  name: 'AppLogo',
  
  props: {
    size: {
      type: String,
      default: 'medium',
      validator: value => ['small', 'medium', 'large'].includes(value)
    },
    className: {
      type: String,
      default: ''
    },
    fallbackLogo: {
      type: String,
      default: ''
    }
  },
  
  data() {
    return {
      logoUrl: '',
      logoAlt: 'Application Logo',
      isLoading: true,
      hasError: false
    };
  },
  
  created() {
    this.fetchLogo();
  },
  
  methods: {
    async fetchLogo() {
      this.isLoading = true;
      this.hasError = false;
      
      try {
        const logoData = await logoService.getLogo();
        this.logoUrl = logoData.logo_url;
        this.logoAlt = logoData.alt_text;
      } catch (error) {
        this.handleError(error);
      } finally {
        this.isLoading = false;
      }
    },
    
    handleError(error) {
      console.error('Error loading logo:', error);
      this.hasError = true;
      this.logoUrl = this.fallbackLogo || '/logo-dark.png';
      this.logoAlt = 'Application Logo';
      
      // Optional: Emit error event for parent components
      this.$emit('error', error);
    }
  }
};
</script>

<style scoped>
.app-logo {
  display: inline-block;
  position: relative;
}

.app-logo__loading {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 40px;
}

.loading-spinner {
  /* Add your spinner styling here */
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 2px solid rgba(0, 0, 0, 0.1);
  border-top-color: #3498db;
  border-radius: 50%;
  animation: spin 1s infinite linear;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.app-logo__image--small {
  height: 30px;
}

.app-logo__image--medium {
  height: 40px;
}

.app-logo__image--large {
  height: 60px;
}
</style>
```

## Usage Examples

### Basic Usage
```vue
<template>
  <div class="header">
    <AppLogo />
    <nav><!-- Navigation items --></nav>
  </div>
</template>

<script>
import AppLogo from '@/components/AppLogo.vue';

export default {
  components: {
    AppLogo
  }
};
</script>
```

### With Custom Size and Class
```vue
<template>
  <div class="login-page">
    <AppLogo size="large" className="login-logo" />
    <!-- Login form -->
  </div>
</template>
```

### With Error Handling
```vue
<template>
  <div class="header">
    <AppLogo @error="handleLogoError" />
  </div>
</template>

<script>
import AppLogo from '@/components/AppLogo.vue';

export default {
  components: {
    AppLogo
  },
  methods: {
    handleLogoError(error) {
      console.error('Logo loading failed:', error);
      // Show notification or take other actions
      this.$toast.error('Failed to load application logo');
    }
  }
};
</script>
```

## Testing Requirements

### Unit Tests
- Test that the component renders correctly with default props
- Test that the component makes the correct API call
- Test that the component handles loading state correctly
- Test that the component handles error state correctly
- Test that the component renders the logo with the correct URL and alt text
- Test that the component applies the correct classes based on props

### Integration Tests
- Test that the component integrates correctly with the API
- Test that the component displays the correct logo from the API response
- Test that the component falls back to the default logo when the API fails

## Accessibility Requirements
- Ensure the logo has appropriate alt text
- Ensure the loading state is accessible to screen readers
- Ensure the error state is accessible to screen readers

## Performance Considerations
- Consider caching the logo data to avoid unnecessary API calls
- Consider lazy loading the logo image
- Consider using a CDN for serving the logo image
