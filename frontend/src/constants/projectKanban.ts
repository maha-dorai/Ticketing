/** Colonnes Kanban projet (statut cycle de vie) */
export const PROJECT_KANBAN_COLUMNS = [
  { id: 'ouvert', title: 'Ouverts', badge: 'Ouvert', status: 'open' as const },
  { id: 'en_cours', title: 'En cours', badge: 'En cours', status: 'progress' as const },
  { id: 'archive', title: 'Fermés', badge: 'Fermé', status: 'closed' as const },
];

export type ProjectKanbanStatus = (typeof PROJECT_KANBAN_COLUMNS)[number]['status'];
