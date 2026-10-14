import React from 'react';

export const LoadingState: React.FC = () => {
  const skeletonCards = Array.from({ length: 6 });

  return (
    <div className="project-grid" aria-busy="true" aria-label="Loading projects">
      {skeletonCards.map((_, index) => (
        <div key={index} className="skeleton-card">
          <div className="skeleton-media shimmer" />
          <div className="skeleton-body">
            <div className="skeleton-meta shimmer" />
            <div className="skeleton-title shimmer" />
            <div className="skeleton-excerpt shimmer" />
            <div className="skeleton-excerpt-short shimmer" />
            <div className="skeleton-chips">
              <span className="skeleton-chip shimmer" />
              <span className="skeleton-chip shimmer" />
              <span className="skeleton-chip shimmer" />
            </div>
          </div>
          <div className="skeleton-footer">
            <div className="skeleton-btn shimmer" />
            <div className="skeleton-btn shimmer" />
          </div>
        </div>
      ))}
    </div>
  );
};
