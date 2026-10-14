import { useState, useEffect, useRef, useCallback } from 'react';
import type { Project, Industry, PaginationInfo } from '../types/project';
import { fetchProjects, fetchIndustries } from '../services/api';
import { useDebounce } from './useDebounce';

interface UseProjectsResult {
  projects: Project[];
  industries: Industry[];
  pagination: PaginationInfo;
  loading: boolean;
  error: string | null;
  search: string;
  industry: string;
  page: number;
  isFallback: boolean;
  setSearch: (search: string) => void;
  setIndustry: (industry: string) => void;
  setPage: (page: number) => void;
  resetFilters: () => void;
  retry: () => void;
}

const DEFAULT_PAGINATION: PaginationInfo = {
  total: 0,
  total_pages: 1,
  current_page: 1,
  per_page: 6,
  has_next: false,
  has_prev: false,
};

export function useProjects(): UseProjectsResult {
  const [search, setSearch] = useState<string>('');
  const [industry, setIndustry] = useState<string>('');
  const [page, setPage] = useState<number>(1);
  const [projects, setProjects] = useState<Project[]>([]);
  const [industries, setIndustries] = useState<Industry[]>([]);
  const [pagination, setPagination] = useState<PaginationInfo>(DEFAULT_PAGINATION);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [isFallback, setIsFallback] = useState<boolean>(false);

  // 350ms debounced search query.
  const debouncedSearch = useDebounce(search, 350);

  // Reference for managing AbortController instances across renders.
  const abortControllerRef = useRef<AbortController | null>(null);

  // Reset page to 1 when search query changes.
  useEffect(() => {
    setPage(1);
  }, [debouncedSearch]);

  // Reset page to 1 when industry filter changes.
  useEffect(() => {
    setPage(1);
  }, [industry]);

  // Load available industries once on mount.
  useEffect(() => {
    const controller = new AbortController();
    fetchIndustries(controller.signal)
      .then((data) => setIndustries(data))
      .catch((err) => {
        if (err.name !== 'AbortError') {
          console.warn('Could not load industries from API:', err);
        }
      });

    return () => {
      controller.abort();
    };
  }, []);

  // Fetch projects matching current filter parameters with AbortController.
  const loadProjects = useCallback(async () => {
    // Cancel any in-flight request before starting a new one.
    if (abortControllerRef.current) {
      abortControllerRef.current.abort();
    }

    const controller = new AbortController();
    abortControllerRef.current = controller;

    setLoading(true);
    setError(null);

    try {
      const response = await fetchProjects(
        {
          search: debouncedSearch,
          industry: industry,
          page: page,
          per_page: 6,
        },
        controller.signal
      );

      setProjects(response.data);
      setPagination(response.pagination);
      setIsFallback(Boolean(response.isFallback));
      setError(null);
    } catch (err: unknown) {
      if (err instanceof DOMException && err.name === 'AbortError') {
        // Request was deliberately superseded by newer query; ignore quietly.
        return;
      }
      const message =
        err instanceof Error ? err.message : 'An unexpected error occurred while fetching projects.';
      setError(message);
    } finally {
      setLoading(false);
    }
  }, [debouncedSearch, industry, page]);

  useEffect(() => {
    loadProjects();

    return () => {
      if (abortControllerRef.current) {
        abortControllerRef.current.abort();
      }
    };
  }, [loadProjects]);

  const resetFilters = useCallback(() => {
    setSearch('');
    setIndustry('');
    setPage(1);
  }, []);

  const retry = useCallback(() => {
    loadProjects();
  }, [loadProjects]);

  return {
    projects,
    industries,
    pagination,
    loading,
    error,
    search,
    industry,
    page,
    isFallback,
    setSearch,
    setIndustry,
    setPage,
    resetFilters,
    retry,
  };
}
