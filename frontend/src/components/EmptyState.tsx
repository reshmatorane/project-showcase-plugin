import React from 'react';
import { FolderEmptyIcon } from './Icons';

interface EmptyStateProps {
  searchQuery?: string;
  industry?: string;
  onResetFilters: () => void;
}

export const EmptyState: React.FC<EmptyStateProps> = ({
  searchQuery,
  industry,
  onResetFilters,
}) => {
  return (
    <div className="empty-state-card" role="status" aria-live="polite">
      <div className="empty-state-icon" aria-hidden="true">
        <FolderEmptyIcon size={56} />
      </div>
      <h3 className="empty-state-title">No Projects Found</h3>
      <p className="empty-state-description">
        {searchQuery && industry
          ? `No projects matched "${searchQuery}" in the selected industry.`
          : searchQuery
          ? `No projects matched your search for "${searchQuery}".`
          : industry
          ? `No projects found in this industry category.`
          : 'There are currently no projects matching your filter criteria.'}
      </p>
      <div className="empty-state-actions">
        <button
          type="button"
          className="btn-primary btn-reset-filters"
          onClick={onResetFilters}
        >
          Clear All Filters
        </button>
      </div>
    </div>
  );
};
