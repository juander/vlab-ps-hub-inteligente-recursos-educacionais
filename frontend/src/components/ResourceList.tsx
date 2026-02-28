import type { Resource } from '../types/resource';

type ResourceListProps = {
  resources: Resource[];
  loading: boolean;
  onEdit: (resource: Resource) => void;
  onDelete: (resource: Resource) => void;
  emptyMessage?: string;
};

export function ResourceList({ resources, loading, onEdit, onDelete, emptyMessage }: ResourceListProps) {
  if (loading) {
    return (
      <p className="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
        Carregando recursos...
      </p>
    );
  }

  if (resources.length === 0) {
    return (
      <p className="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600 shadow-sm">
        {emptyMessage ?? 'Nenhum recurso cadastrado.'}
      </p>
    );
  }

  return (
    <div className="max-h-[62vh] space-y-3 overflow-y-auto pr-1">
      {resources.map((resource) => (
        <article
          key={resource.id}
          className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        >
          <div className="flex flex-wrap items-center justify-between gap-2">
            <h3 className="text-base font-semibold text-slate-900">{resource.title}</h3>
            <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
              {resource.type}
            </span>
          </div>

          <p className="mt-2 text-sm text-slate-700">{resource.description}</p>
          <a
            href={resource.url}
            target="_blank"
            rel="noreferrer"
            className="mt-2 inline-block break-all text-sm text-indigo-600 hover:text-indigo-500"
          >
            {resource.url}
          </a>

          <div className="mt-3 flex flex-wrap gap-2">
            {resource.tags.length === 0 && (
              <span className="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-500">Sem tags</span>
            )}
            {resource.tags.map((tag) => (
              <span
                key={`${resource.id}-${tag}`}
                className="rounded-full border border-emerald-100 bg-emerald-50 px-2 py-1 text-xs text-emerald-700"
              >
                {tag}
              </span>
            ))}
          </div>

          <div className="mt-4 flex gap-2">
            <button
              onClick={() => onEdit(resource)}
              className="rounded-md bg-amber-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-amber-400"
            >
              Editar
            </button>
            <button
              onClick={() => onDelete(resource)}
              className="rounded-md bg-rose-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-rose-500"
            >
              Excluir
            </button>
          </div>
        </article>
      ))}
    </div>
  );
}
