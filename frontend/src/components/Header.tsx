import React from 'react';

interface HeaderProps {
  totalProjects: number;
  isFallback: boolean;
}

export const Header: React.FC<HeaderProps> = ({ totalProjects, isFallback }) => {
  return (
    <header className="app-header">
      <div className="header-container">
        <div className="header-brand-section">
          <div className="brand-logo-badge">
            <span className="logo-symbol">PS</span>
          </div>
          <div>
            <h1 className="header-title">Project Showcase</h1>
            <p className="header-subtitle">
              Enterprise Portfolio Architecture with WordPress REST API & React
            </p>
          </div>
        </div>

        <div className="header-status-section">
          {isFallback ? (
            <span
              className="status-pill status-demo"
              title="Demonstration dataset active (start WordPress backend container to connect live API)"
            >
              <span className="status-dot dot-amber" />
              <span>Offline / Demo Mode</span>
            </span>
          ) : (
            <span
              className="status-pill status-live"
              title="Connected to WordPress REST API (showcase/v1)"
            >
              <span className="status-dot dot-green" />
              <span>Live WP REST API</span>
            </span>
          )}

          <span className="status-pill status-count">
            <span className="count-number">{totalProjects}</span>
            <span>Projects</span>
          </span>
        </div>
      </div>
    </header>
  );
};
