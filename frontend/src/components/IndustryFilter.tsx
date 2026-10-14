import type { Industry } from '../types/project';

interface IndustryFilterProps {
  industries: Industry[];
  selectedIndustry: string;
  onSelectIndustry: (slug: string) => void;
  totalProjectsCount?: number;
}

export const IndustryFilter: React.FC<IndustryFilterProps> = ({
  industries,
  selectedIndustry,
  onSelectIndustry,
  totalProjectsCount,
}) => {
  return (
    <nav className="industry-filter-nav" aria-label="Filter by industry">
      <div className="industry-pill-list" role="tablist">
        {/* All Industries Pill */}
        <button
          type="button"
          role="tab"
          aria-selected={selectedIndustry === ''}
          className={`industry-pill ${selectedIndustry === '' ? 'active' : ''}`}
          onClick={() => onSelectIndustry('')}
        >
          <span>All Industries</span>
          {typeof totalProjectsCount === 'number' && totalProjectsCount > 0 && (
            <span className="pill-count" aria-hidden="true">
              {totalProjectsCount}
            </span>
          )}
        </button>

        {/* Individual Industry Pills */}
        {industries.map((ind) => {
          const isSelected = selectedIndustry === ind.slug;
          return (
            <button
              key={ind.id}
              type="button"
              role="tab"
              aria-selected={isSelected}
              className={`industry-pill ${isSelected ? 'active' : ''}`}
              onClick={() => onSelectIndustry(isSelected ? '' : ind.slug)}
            >
              <span>{ind.name}</span>
              {typeof ind.count === 'number' && ind.count > 0 && (
                <span className="pill-count" aria-hidden="true">
                  {ind.count}
                </span>
              )}
            </button>
          );
        })}
      </div>
    </nav>
  );
};
