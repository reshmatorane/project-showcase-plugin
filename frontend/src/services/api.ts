/**
 * Project Showcase API Service Layer.
 *
 * Communicates with the custom WordPress REST API (GET /wp-json/showcase/v1/projects).
 * Supports request cancellation via AbortSignal and comprehensive HTTP error handling.
 */

import type {
  Project,
  Industry,
  ProjectsResponse,
  ProjectQueryParams,
} from '../types/project';

const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || '/wp-json/showcase/v1'

/**
 * Fetch projects from WordPress REST API.
 */
export async function fetchProjects(
  params: ProjectQueryParams = {},
  signal?: AbortSignal
): Promise<ProjectsResponse> {
  const query = new URLSearchParams();

  if (params.search) query.set('search', params.search);
  if (params.industry) query.set('industry', params.industry);
  if (params.page) query.set('page', String(params.page));
  if (params.per_page) query.set('per_page', String(params.per_page));
  if (params.orderby) query.set('orderby', params.orderby);
  if (params.order) query.set('order', params.order);

  const endpoint = `${API_BASE_URL}/projects${query.toString() ? `?${query.toString()}` : ''}`;

  const response = await fetch(endpoint, {
    signal,
    headers: { Accept: 'application/json' },
  });

  if (!response.ok) {
    const errorText = await response.text();
    let message = `API request failed with status ${response.status} (${response.statusText})`;
    try {
      const parsed = JSON.parse(errorText);
      if (parsed.message) message = parsed.message;
    } catch {
      // ignore JSON parse errors
    }
    throw new Error(message);
  }

  const json = await response.json();
  return { ...json, isFallback: false };
}

/**
 * Fetch industries from WordPress REST API.
 * If the endpoint is unavailable, returns an empty array; the UI can derive industries from projects.
 */
export async function fetchIndustries(signal?: AbortSignal): Promise<Industry[]> {
  const endpoint = `${API_BASE_URL}/industries`;

  try {
    const response = await fetch(endpoint, {
      signal,
      headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
      throw new Error(`Failed to load industries: ${response.status}`);
    }

    const json = await response.json();
    return json.data || [];
  } catch (err: unknown) {
    if (err && typeof err === 'object' && 'name' in err && (err as any).name === 'AbortError') {
      throw err;
    }
    // Return empty array for fallback – UI may derive from projects
    return [];
  }
}
