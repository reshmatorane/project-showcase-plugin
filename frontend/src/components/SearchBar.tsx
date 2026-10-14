import React from 'react';
import { SearchIcon, CloseIcon } from './Icons';

interface SearchBarProps {
  value: string;
  onChange: (value: string) => void;
  isLoading?: boolean;
}

export const SearchBar: React.FC<SearchBarProps> = ({
  value,
  onChange,
  isLoading = false,
}) => {
  const handleClear = () => {
    onChange('');
  };

  return (
    <div className="search-bar-wrapper">
      <label htmlFor="project-search-input" className="sr-only">
        Search projects by title, client, or technology
      </label>
      <div className="search-input-container">
        <span className="search-icon-adornment" aria-hidden="true">
          <SearchIcon size={18} />
        </span>
        <input
          id="project-search-input"
          type="search"
          className="search-input"
          placeholder="Search by project, client, or tech stack..."
          value={value}
          onChange={(e) => onChange(e.target.value)}
          autoComplete="off"
          spellCheck="false"
        />
        {isLoading && (
          <span className="search-spinner" aria-label="Loading search results" />
        )}
        {value && !isLoading && (
          <button
            type="button"
            className="search-clear-btn"
            onClick={handleClear}
            aria-label="Clear search query"
          >
            <CloseIcon size={14} />
          </button>
        )}
      </div>
    </div>
  );
};
