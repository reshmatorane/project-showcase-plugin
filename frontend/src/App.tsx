import React, { useState } from 'react';
import { useProjects } from './hooks/useProjects';
import { Header } from './components/Header';
import { SearchBar } from './components/SearchBar';
import { IndustryFilter } from './components/IndustryFilter';
import { ProjectGrid } from './components/ProjectGrid';
import { Pagination } from './components/Pagination';
import { LoadingState } from './components/LoadingState';
import { EmptyState } from './components/EmptyState';
import { ErrorState } from './components/ErrorState';
import { ProjectModal } from './components/ProjectModal';
import type { Project } from './types/project';

export const App: React.FC = () => {
  const {
    projects,
    industries,
    pagination,
    loading,
    error,
    search,
    industry,
    isFallback,
    setSearch,
    setIndustry,
    setPage,
    resetFilters,
    retry,
  } = useProjects();

  const [activeModalProject, setActiveModalProject] = useState<Project | null>(null);

  return (
    <div className="app-layout">
      {/* Top Header */}
      <Header totalProjects={pagination.total} isFallback={isFallback} />

      <main className="main-content" id="main-content">
        <div className="content-container">
          {/* Controls Bar: Search & Industry Filters */}
          <section className="controls-panel" aria-label="Search and filter controls">
            <SearchBar value={search} onChange={setSearch} isLoading={loading} />

            <IndustryFilter
              industries={industries}
              selectedIndustry={industry}
              onSelectIndustry={setIndustry}
              totalProjectsCount={pagination.total}
            />
          </section>

          {/* Dynamic Content Views */}
          {error ? (
            <ErrorState message={error} onRetry={retry} />
          ) : loading ? (
            <LoadingState />
          ) : projects.length === 0 ? (
            <EmptyState
              searchQuery={search}
              industry={industry}
              onResetFilters={resetFilters}
            />
          ) : (
            <>
              <ProjectGrid
                projects={projects}
                onOpenDetails={setActiveModalProject}
              />

              <Pagination
                pagination={pagination}
                onPageChange={setPage}
              />
            </>
          )}
        </div>
      </main>

      {/* Case Study Details Modal */}
      <ProjectModal
        project={activeModalProject}
        onClose={() => setActiveModalProject(null)}
      />

      {/* Footer */}
      <footer className="app-footer">
        <div className="footer-container">
          <p>
            Project Showcase &bull; Full-Stack WordPress + React Architecture
          </p>
          <p className="footer-tech-note">
            Built with pure WordPress PHP classes, transient caching, and React 18 + Vite + TypeScript.
          </p>
        </div>
      </footer>
    </div>
  );
};

export default App;
