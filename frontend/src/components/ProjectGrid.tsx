import type { Project } from '../types/project';
import { ProjectCard } from './ProjectCard';

interface ProjectGridProps {
  projects: Project[];
  onOpenDetails: (project: Project) => void;
}

export const ProjectGrid: React.FC<ProjectGridProps> = ({
  projects,
  onOpenDetails,
}) => {
  return (
    <section className="project-grid-section" aria-label="Projects list">
      <div className="project-grid">
        {projects.map((project) => (
          <ProjectCard
            key={project.id}
            project={project}
            onOpenDetails={onOpenDetails}
          />
        ))}
      </div>
    </section>
  );
};
