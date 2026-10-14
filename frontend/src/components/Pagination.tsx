import type { PaginationInfo } from '../types/project';
import { ChevronLeftIcon, ChevronRightIcon } from './Icons';

interface PaginationProps {
  pagination: PaginationInfo;
  onPageChange: (newPage: number) => void;
}

export const Pagination: React.FC<PaginationProps> = ({
  pagination,
  onPageChange,
}) => {
  const { total, total_pages, current_page, per_page, has_next, has_prev } = pagination;

  if (total_pages <= 1) {
    return null;
  }

  // Calculate range items showing (e.g. 1-6 of 10)
  const from = (current_page - 1) * per_page + 1;
  const to = Math.min(current_page * per_page, total);

  // Generate page numbers array
  const pages: number[] = [];
  for (let i = 1; i <= total_pages; i++) {
    pages.push(i);
  }

  return (
    <nav className="pagination-container" aria-label="Projects pagination">
      {/* Result Range Information */}
      <div className="pagination-summary" aria-live="polite">
        Showing <span className="font-semibold">{from}</span> to{' '}
        <span className="font-semibold">{to}</span> of{' '}
        <span className="font-semibold">{total}</span> projects
      </div>

      {/* Page Buttons List */}
      <div className="pagination-controls">
        {/* Previous Button */}
        <button
          type="button"
          className="btn-pagination-nav"
          onClick={() => onPageChange(current_page - 1)}
          disabled={!has_prev}
          aria-label="Go to previous page"
        >
          <ChevronLeftIcon size={16} />
          <span>Previous</span>
        </button>

        {/* Numeric Page Buttons */}
        <div className="pagination-numbers">
          {pages.map((p) => {
            const isCurrent = p === current_page;
            return (
              <button
                key={p}
                type="button"
                className={`btn-page-number ${isCurrent ? 'active' : ''}`}
                onClick={() => onPageChange(p)}
                aria-current={isCurrent ? 'page' : undefined}
                aria-label={`Page ${p}`}
              >
                {p}
              </button>
            );
          })}
        </div>

        {/* Next Button */}
        <button
          type="button"
          className="btn-pagination-nav"
          onClick={() => onPageChange(current_page + 1)}
          disabled={!has_next}
          aria-label="Go to next page"
        >
          <span>Next</span>
          <ChevronRightIcon size={16} />
        </button>
      </div>
    </nav>
  );
};
