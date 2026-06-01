/**
 * Base URL for Laravel public storage (no trailing slash).
 * Derived from VITE_API_URL by stripping the /api suffix.
 */
export function getStorageBaseUrl(): string {
  const apiUrl = (import.meta.env.VITE_API_URL as string | undefined) ?? 'http://localhost:8000/api';
  return apiUrl.replace(/\/api\/?$/, '');
}

export function storageAssetUrl(filePath: string): string {
  const path = filePath.replace(/^\//, '');
  return `${getStorageBaseUrl()}/storage/${path}`;
}
