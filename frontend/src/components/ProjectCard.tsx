import type { Project } from '../types/project';
import {
  ExternalLinkIcon,
  CalendarIcon,
  UserIcon,
  LayersIcon,
} from './Icons';

interface ProjectCardProps {
  project: Project;
  onOpenDetails: (project: Project) => void;
}

export const ProjectCard: React.FC<ProjectCardProps> = ({
  project,
  onOpenDetails,
}) => {
  const { title, excerpt, industries, meta, featured_image } = project;

  // Format date cleanly (e.g., 'Mar 2024')
  const formattedDate = meta.launch_date
    ? new Date(meta.launch_date).toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
      })
    : null;

  return (
    <article className="project-card" aria-labelledby={`project-title-${project.id}`}>
      {/* Visual Header / Cover Media */}
      <div className="card-media-wrapper">
        {featured_image ? (
          <img
            src={featured_image.url}
            alt={featured_image.alt || `${title} cover image`}
            className="card-media-img"
            loading="lazy"
          />
        ) : (
          <div className="card-media-placeholder" aria-hidden="true">
            <span className="placeholder-pattern" />
            <div className="placeholder-brand">
              <span className="brand-dot" />
              <span>{industries[0]?.name || 'Showcase'}</span>
            </div>
          </div>
        )}

        {/* Industry Pill Overlays */}
        {industries.length > 0 && (
          <div className="card-badges-overlay">
            {industries.map((ind) => (
              <span key={ind.id} className="badge-industry">
                {ind.name}
              </span>
            ))}
          </div>
        )}
      </div>

      {/* Card Content Body */}
      <div className="card-body">
        {/* Meta Bar: Client & Launch Date */}
        <div className="card-meta-bar">
          {meta.client_name && (
            <span className="meta-item meta-client" title={`Client: ${meta.client_name}`}>
              <UserIcon size={13} />
              <span className="truncate">{meta.client_name}</span>
            </span>
          )}
          {formattedDate && (
            <span className="meta-item meta-date" title={`Launched: ${formattedDate}`}>
              <CalendarIcon size={13} />
              <span>{formattedDate}</span>
            </span>
          )}
        </div>

        {/* Title */}
        <h3 id={`project-title-${project.id}`} className="card-title">
          <button
            type="button"
            className="card-title-btn"
            onClick={() => onOpenDetails(project)}
          >
            {title}
          </button>
        </h3>

        {/* Excerpt */}
        <p className="card-excerpt">{excerpt}</p>

        {/* Tech Stack Chips */}
        {meta.tech_stack && meta.tech_stack.length > 0 && (
          <div className="card-stack-section">
            <div className="card-stack-chips">
              {meta.tech_stack.slice(0, 4).map((tech, i) => (
                <span key={i} className="tech-chip">
                  {tech}
                </span>
              ))}
              {meta.tech_stack.length > 4 && (
                <span className="tech-chip tech-chip-more">
                  +{meta.tech_stack.length - 4}
                </span>
              )}
            </div>
          </div>
        )}
      </div>

      {/* Card Action Footer */}
      <div className="card-footer">
        <button
          type="button"
          className="btn-card-action btn-secondary"
          onClick={() => onOpenDetails(project)}
          aria-label={`View full details for ${title}`}
        >
          <LayersIcon size={14} />
          <span>Case Study</span>
        </button>

        {meta.project_url && (
          <a
            href={meta.project_url}
            target="_blank"
            rel="noopener noreferrer"
            className="btn-card-action btn-primary"
            aria-label={`Visit live website for ${title} (opens in new tab)`}
          >
            <span>Live Demo</span>
            <ExternalLinkIcon size={14} />
          </a>
        )}
      </div>
    </article>
  );
};
