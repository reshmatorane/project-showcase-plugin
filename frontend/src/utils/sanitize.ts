/**
 * Lightweight safe HTML sanitizer for WordPress post content.
 * Strips script tags, iframes, inline event handlers (on*), and javascript: protocol URLs.
 */
export function sanitizeHtml(rawHtml: string): string {
  if (!rawHtml) return '';

  return rawHtml
    // Remove script tags and contents
    .replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
    // Remove iframe tags
    .replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, '')
    // Remove object / embed tags
    .replace(/<(object|embed)\b[^<]*(?:(?!<\/\1>)<[^<]*)*<\/\1>/gi, '')
    // Remove inline event handler attributes (e.g. onclick, onload, onerror)
    .replace(/\s*on[a-z]+\s*=\s*(['"][^'"]*['"]|[^\s>]+)/gi, '')
    // Remove javascript: and data: URLs in href/src
    .replace(/(href|src)\s*=\s*['"]\s*(javascript|data):[^'"]*['"]/gi, '$1="#"');
}
