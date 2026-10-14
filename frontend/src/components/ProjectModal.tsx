import React, { useEffect, useRef } from 'react';
import type { Project } from '../types/project';
import {
  CloseIcon,
  ExternalLinkIcon,
  CalendarIcon,
  UserIcon,
} from './Icons';

import { sanitizeHtml } from '../utils/sanitize';

interface ProjectModalProps {
  project: Project | null;
  onClose: () => void;
}

export const ProjectModal: React.FC<ProjectModalProps> = ({ project, onClose }) => {
  const modalRef = useRef<HTMLDivElement>(null);
  const closeBtnRef = useRef<HTMLButtonElement>(null);

  // Close modal when Escape key is pressed
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    if (project) {
      document.body.style.overflow = 'hidden';
      window.addEventListener('keydown', handleKeyDown);
      closeBtnRef.current?.focus();
    }

    return () => {
      document.body.style.overflow = '';
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [project, onClose]);

  if (!project) {
    return null;
  }

  const { title, excerpt, content, industries, meta, featured_image } = project;

  const formattedDate = meta.launch_date
    ? new Date(meta.launch_date).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })
    : null;

  return (
    <div
      className="modal-backdrop"
      onClick={(e) => {
        if (e.target === e.currentTarget) onClose();
      }}
      role="presentation"
    >
      <div
        ref={modalRef}
        className="modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-project-title"
      >
        {/* Modal Header */}
        <div className="modal-header">
          <div className="modal-header-meta">
            {industries.map((ind) => (
              <span key={ind.id} className="badge-industry">
                {ind.name}
              </span>
            ))}
          </div>
          <button
            ref={closeBtnRef}
            type="button"
            className="modal-close-btn"
            onClick={onClose}
            aria-label="Close project details"
          >
            <CloseIcon size={18} />
          </button>
        </div>

        {/* Modal Scrollable Body */}
        <div className="modal-body">
          {featured_image && (
            <div className="modal-image-wrapper">
              <img
                src={featured_image.url}
                alt={featured_image.alt || title}
                className="modal-image"
              />
            </div>
          )}

          <h2 id="modal-project-title" className="modal-title">
            {title}
          </h2>

          <div className="modal-meta-grid">
            {meta.client_name && (
              <div className="modal-meta-card">
                <span className="modal-meta-label">
                  <UserIcon size={14} />
                  <span>Client Organization</span>
                </span>
                <span className="modal-meta-val">{meta.client_name}</span>
              </div>
            )}
            {formattedDate && (
              <div className="modal-meta-card">
                <span className="modal-meta-label">
                  <CalendarIcon size={14} />
                  <span>Launch Date</span>
                </span>
                <span className="modal-meta-val">{formattedDate}</span>
              </div>
            )}
          </div>

          <div className="modal-summary">
            <h4 className="modal-section-title">Overview</h4>
            <p className="modal-excerpt-text">{excerpt}</p>
          </div>

          {meta.tech_stack && meta.tech_stack.length > 0 && (
            <div className="modal-tech-stack">
              <h4 className="modal-section-title">Technologies & Architecture</h4>
              <div className="modal-stack-chips">
                {meta.tech_stack.map((tech, i) => (
                  <span key={i} className="tech-chip tech-chip-large">
                    {tech}
                  </span>
                ))}
              </div>
            </div>
          )}

          {content && (
            <div className="modal-narrative">
              <h4 className="modal-section-title">Case Study Details</h4>
              <div
                className="modal-content-html"
                dangerouslySetInnerHTML={{ __html: sanitizeHtml(content) }}
              />
            </div>
          )}
        </div>

        {/* Modal Action Footer */}
        <div className="modal-footer">
          <button type="button" className="btn-secondary" onClick={onClose}>
            Close
          </button>
          {meta.project_url && (
            <a
              href={meta.project_url}
              target="_blank"
              rel="noopener noreferrer"
              className="btn-primary"
            >
              <span>Visit Live Project</span>
              <ExternalLinkIcon size={14} />
            </a>
          )}
        </div>
      </div>
    </div>
  );
};
