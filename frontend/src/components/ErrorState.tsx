import React from 'react';
import { AlertTriangleIcon, RefreshCwIcon } from './Icons';

interface ErrorStateProps {
  message: string;
  onRetry: () => void;
}

export const ErrorState: React.FC<ErrorStateProps> = ({ message, onRetry }) => {
  return (
    <div className="error-state-card" role="alert" aria-live="assertive">
      <div className="error-state-header">
        <span className="error-icon" aria-hidden="true">
          <AlertTriangleIcon size={24} />
        </span>
        <div className="error-text">
          <h4 className="error-title">Unable to Connect to Project Showcase</h4>
          <p className="error-message">{message}</p>
        </div>
      </div>
      <div className="error-actions">
        <button type="button" className="btn-secondary btn-retry" onClick={onRetry}>
          <RefreshCwIcon size={14} />
          <span>Retry Request</span>
        </button>
      </div>
    </div>
  );
};
