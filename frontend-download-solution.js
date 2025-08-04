/**
 * Frontend solution for downloading files from the Laravel backend
 *
 * This code can be integrated into your Vue.js application to handle
 * file downloads properly across origins.
 */

/**
 * Download a file using a streaming approach
 * @param {string} fileName - The path to the file in storage
 * @param {string} apiUrl - The base API URL (default: http://localhost:8000/api)
 * @returns {Promise<void>}
 */
async function downloadFile(fileName, apiUrl = 'http://localhost:8000/api') {
  try {
    // Create a URL for the download endpoint
    const url = `${apiUrl}/documents/download-signed`;

    // Get the CSRF token from the cookie if available
    const csrfToken = getCsrfToken();

    // Prepare headers with CSRF token if available
    const headers = {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    };

    if (csrfToken) {
      headers['X-CSRF-TOKEN'] = csrfToken;
    }

    // Make the request using fetch with proper credentials
    const response = await fetch(url, {
      method: 'POST',
      headers: headers,
      credentials: 'include', // Important for CSRF cookies
      body: JSON.stringify({ file_name: fileName })
    });

    if (!response.ok) {
      throw new Error(`Download failed: ${response.status} ${response.statusText}`);
    }

    // Get the filename from Content-Disposition header or use the basename
    let downloadFilename = fileName.split('/').pop();
    const contentDisposition = response.headers.get('Content-Disposition');
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="(.+)"/);
      if (filenameMatch && filenameMatch[1]) {
        downloadFilename = filenameMatch[1];
      }
    }

    // Convert the response to a blob
    const blob = await response.blob();

    // Create a download link and trigger the download
    const downloadUrl = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = downloadUrl;
    a.download = downloadFilename;
    document.body.appendChild(a);
    a.click();

    // Clean up
    window.URL.revokeObjectURL(downloadUrl);
    document.body.removeChild(a);

    return true;
  } catch (error) {
    console.error('Error downloading file:', error);
    throw error;
  }
}

/**
 * Get the CSRF token from the cookie
 * @returns {string|null}
 */
function getCsrfToken() {
  const tokenCookie = document.cookie
    .split('; ')
    .find(cookie => cookie.startsWith('XSRF-TOKEN='));

  if (tokenCookie) {
    return decodeURIComponent(tokenCookie.split('=')[1]);
  }

  return null;
}

/**
 * Example usage in a Vue component:
 *
 * <template>
 *   <button @click="handleDownload" :disabled="isDownloading">
 *     {{ isDownloading ? 'Downloading...' : 'Download File' }}
 *   </button>
 * </template>
 *
 * <script>
 * import { downloadFile } from './frontend-download-solution';
 *
 * export default {
 *   data() {
 *     return {
 *       isDownloading: false
 *     };
 *   },
 *   methods: {
 *     async handleDownload() {
 *       this.isDownloading = true;
 *       try {
 *         await downloadFile('signed_documents/your-file.pdf');
 *         this.$toast.success('File downloaded successfully');
 *       } catch (error) {
 *         this.$toast.error('Failed to download file');
 *         console.error(error);
 *       } finally {
 *         this.isDownloading = false;
 *       }
 *     }
 *   }
 * };
 * </script>
 */
