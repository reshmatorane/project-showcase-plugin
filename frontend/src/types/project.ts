/**
 * Project Showcase TypeScript Type Definitions.
 * Strictly aligned with WordPress REST API schema (showcase/v1).
 */

export interface FeaturedImage {
  url: string;
  thumbnail: string;
  alt: string;
}

export interface Industry {
  id: number;
  name: string;
  slug: string;
  count?: number;
  description?: string;
}

export interface ProjectMeta {
  client_name: string;
  project_url: string;
  tech_stack: string[];
  launch_date: string;
}

export interface Project {
  id: number;
  title: string;
  slug: string;
  excerpt: string;
  content: string;
  date: string;
  modified: string;
  featured_image: FeaturedImage | null;
  industries: Industry[];
  meta: ProjectMeta;
}

export interface PaginationInfo {
  total: number;
  total_pages: number;
  current_page: number;
  per_page: number;
  has_next: boolean;
  has_prev: boolean;
}

export interface ProjectsResponse {
  success: boolean;
  data: Project[];
  pagination: PaginationInfo;
  isFallback?: boolean;
}

export interface IndustriesResponse {
  success: boolean;
  data: Industry[];
}

export interface ProjectQueryParams {
  search?: string;
  industry?: string;
  page?: number;
  per_page?: number;
  orderby?: 'date' | 'title' | 'id';
  order?: 'ASC' | 'DESC';
}
