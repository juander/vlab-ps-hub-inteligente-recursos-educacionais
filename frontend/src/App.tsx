import { useEffect, useMemo, useState } from 'react';
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import {
  createResource,
  deleteResource,
  getResources,
  smartAssist,
  updateResource,
} from './api/resources';
import { DeleteResourceModal } from './components/DeleteResourceModal';
import { ResourceForm } from './components/ResourceForm';
import { ResourceList } from './components/ResourceList';
import type { Resource, ResourceFilters, ResourcePayload } from './types/resource';

type AppError = {
  response?: {
    data?: {
      error?: string;
      message?: string;
    };
  };
};

const PER_PAGE = 6;
const COMMON_TYPES = ['video', 'vídeo', 'artigo', 'curso', 'podcast'];

const initialFilters: ResourceFilters = {
  title: '',
  type: '',
  tag: '',
};

export default function App() {
  const queryClient = useQueryClient();
  const [page, setPage] = useState(1);
  const [filters, setFilters] = useState<ResourceFilters>(initialFilters);
  const [debouncedFilters, setDebouncedFilters] = useState<ResourceFilters>(initialFilters);
  const [editingResource, setEditingResource] = useState<Resource | null>(null);
  const [resourceToDelete, setResourceToDelete] = useState<Resource | null>(null);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [knownTypes, setKnownTypes] = useState<string[]>(
    [...COMMON_TYPES].sort((a, b) => a.localeCompare(b, 'pt-BR')),
  );

  useEffect(() => {
    const timeoutId = window.setTimeout(() => {
      setDebouncedFilters({
        title: filters.title.trim(),
        type: filters.type.trim(),
        tag: filters.tag.trim(),
      });
    }, 250);

    return () => window.clearTimeout(timeoutId);
  }, [filters]);

  useEffect(() => {
    setPage(1);
  }, [filters.title, filters.type, filters.tag]);

  const resourcesQuery = useQuery({
    queryKey: ['resources', page, debouncedFilters.title, debouncedFilters.type, debouncedFilters.tag],
    queryFn: () =>
      getResources(page, PER_PAGE, {
        title: debouncedFilters.title,
        type: debouncedFilters.type,
        tag: debouncedFilters.tag,
      }),
    placeholderData: keepPreviousData,
  });

  useEffect(() => {
    const items = resourcesQuery.data?.data ?? [];
    if (items.length === 0) return;

    setKnownTypes((current) => {
      const merged = new Set(current);
      COMMON_TYPES.forEach((type) => merged.add(type));
      items.forEach((resource) => {
        const value = resource.type.trim();
        if (value) merged.add(value);
      });

      return Array.from(merged).sort((a, b) => a.localeCompare(b, 'pt-BR'));
    });
  }, [resourcesQuery.data]);

  const createMutation = useMutation({
    mutationFn: (payload: ResourcePayload) => createResource(payload),
    onSuccess: () => {
      setErrorMessage(null);
      setPage(1);
      void queryClient.invalidateQueries({ queryKey: ['resources'] });
    },
    onError: (error: AppError) => {
      setErrorMessage(error.response?.data?.message ?? 'Falha ao criar recurso.');
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: Partial<ResourcePayload> }) =>
      updateResource(id, payload),
    onSuccess: () => {
      setErrorMessage(null);
      setEditingResource(null);
      void queryClient.invalidateQueries({ queryKey: ['resources'] });
    },
    onError: (error: AppError) => {
      setErrorMessage(error.response?.data?.message ?? 'Falha ao atualizar recurso.');
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => deleteResource(id),
    onSuccess: () => {
      setErrorMessage(null);
      setResourceToDelete(null);
      void queryClient.invalidateQueries({ queryKey: ['resources'] });
    },
    onError: () => {
      setErrorMessage('Falha ao excluir recurso.');
    },
  });

  const aiMutation = useMutation({
    mutationFn: smartAssist,
    onError: (error: AppError) => {
      setErrorMessage(error.response?.data?.error ?? error.response?.data?.message ?? 'Falha ao gerar descrição com IA.');
    },
  });

  const resources = useMemo(() => resourcesQuery.data?.data ?? [], [resourcesQuery.data]);
  const meta = resourcesQuery.data?.meta;

  useEffect(() => {
    if (!meta) return;

    if (meta.last_page === 0 && page !== 1) {
      setPage(1);
      return;
    }

    if (meta.last_page > 0 && page > meta.last_page) {
      setPage(meta.last_page);
    }
  }, [meta, page]);

  const isFilterActive = Boolean(filters.title || filters.type || filters.tag);

  const paginationSummary = useMemo(() => {
    if (!meta || meta.total === 0) {
      return 'Nenhum resultado.';
    }

    const from = (meta.page - 1) * meta.per_page + 1;
    const to = Math.min(meta.page * meta.per_page, meta.total);

    return `Mostrando ${from}-${to} de ${meta.total} recursos`;
  }, [meta]);

  function handleSubmit(payload: ResourcePayload) {
    if (editingResource) {
      updateMutation.mutate({ id: editingResource.id, payload });
      return;
    }

    createMutation.mutate(payload);
  }

  function handleDelete(resource: Resource) {
    setResourceToDelete(resource);
  }

  function confirmDelete(): void {
    if (!resourceToDelete) return;
    deleteMutation.mutate(resourceToDelete.id);
  }

  async function handleGenerateWithAi(input: { title: string; type: string; url: string }) {
    setErrorMessage(null);
    return aiMutation.mutateAsync(input);
  }

  return (
    <main className="mx-auto min-h-screen w-full max-w-7xl p-4 sm:p-6">
      <header className="mb-6 rounded-2xl border border-slate-200 bg-gradient-to-r from-emerald-50 via-white to-sky-50 p-5 shadow-sm">
        <h1 className="text-2xl font-bold text-slate-900">Hub Inteligente de Recursos Educacionais</h1>
        <p className="mt-1 text-sm text-slate-600">
          Organize conteúdos, gere descrições com IA e encontre materiais em segundos.
        </p>

        <div className="mt-4 grid gap-2 text-xs text-slate-600 sm:grid-cols-3">
          <div className="rounded-lg border border-slate-200 bg-white px-3 py-2">1. Preencha título + tipo + URL.</div>
          <div className="rounded-lg border border-slate-200 bg-white px-3 py-2">2. Gere descrição e tags com IA.</div>
          <div className="rounded-lg border border-slate-200 bg-white px-3 py-2">3. Salve e filtre por tipo, título e tag.</div>
        </div>
      </header>

      {errorMessage && (
        <div className="mb-4 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
          {errorMessage}
        </div>
      )}

      <section className="grid gap-6 lg:grid-cols-[1fr_1.15fr]">
        <ResourceForm
          initialData={editingResource}
          loading={createMutation.isPending || updateMutation.isPending}
          aiLoading={aiMutation.isPending}
          onSubmit={handleSubmit}
          onGenerateWithAi={handleGenerateWithAi}
          onCancelEdit={() => setEditingResource(null)}
        />

        <div className="space-y-3">
          <div className="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <h2 className="text-base font-semibold text-slate-800">Busca e filtros</h2>
              {isFilterActive && (
                <button
                  type="button"
                  className="rounded-md border border-slate-300 px-2 py-1 text-xs text-slate-700 transition hover:bg-slate-50"
                  onClick={() => setFilters(initialFilters)}
                >
                  Limpar filtros
                </button>
              )}
            </div>

            <div className="grid gap-2 sm:grid-cols-2">
              <input
                className="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none"
                placeholder="Buscar por título"
                value={filters.title}
                onChange={(event) => setFilters((prev) => ({ ...prev, title: event.target.value }))}
              />

              <input
                className="rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none"
                placeholder="Filtrar por tipo"
                list="resource-types"
                value={filters.type}
                onChange={(event) => setFilters((prev) => ({ ...prev, type: event.target.value }))}
              />
              <datalist id="resource-types">
                {knownTypes.map((type) => (
                  <option key={type} value={type} />
                ))}
              </datalist>
            </div>

            <input
              className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none"
              placeholder="Filtrar por tag"
              value={filters.tag}
              onChange={(event) => setFilters((prev) => ({ ...prev, tag: event.target.value }))}
            />

            <div className="flex flex-wrap gap-2">
              {knownTypes.slice(0, 6).map((type) => (
                <button
                  key={type}
                  type="button"
                  className="rounded-full border border-slate-300 px-2.5 py-1 text-xs text-slate-700 transition hover:border-emerald-400 hover:text-emerald-700"
                  onClick={() => setFilters((prev) => ({ ...prev, type }))}
                >
                  {type}
                </button>
              ))}
            </div>

            <p className="text-xs text-slate-500">Dica: combine filtros para resultados mais precisos.</p>
          </div>

          <ResourceList
            resources={resources}
            loading={resourcesQuery.isLoading}
            onEdit={setEditingResource}
            onDelete={handleDelete}
            emptyMessage={
              isFilterActive
                ? 'Nenhum recurso encontrado com os filtros informados.'
                : 'Nenhum recurso cadastrado.'
            }
          />

          <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm">
            <span className="text-slate-700">{paginationSummary}</span>
            <div className="flex items-center gap-2">
              <span className="text-slate-600">
                Página {meta?.page ?? 1} de {meta?.last_page ?? 1}
              </span>
              <button
                className="rounded-md bg-slate-100 px-3 py-1 text-slate-700 transition hover:bg-slate-200 disabled:cursor-not-allowed disabled:opacity-50"
                onClick={() => setPage((current) => Math.max(current - 1, 1))}
                disabled={(meta?.page ?? 1) <= 1 || resourcesQuery.isFetching}
              >
                Anterior
              </button>
              <button
                className="rounded-md bg-slate-900 px-3 py-1 text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                onClick={() => setPage((current) => current + 1)}
                disabled={(meta?.page ?? 1) >= (meta?.last_page ?? 1) || resourcesQuery.isFetching}
              >
                Próxima
              </button>
            </div>
          </div>
        </div>
      </section>

      <DeleteResourceModal
        open={Boolean(resourceToDelete)}
        resourceTitle={resourceToDelete?.title ?? ''}
        loading={deleteMutation.isPending}
        onCancel={() => setResourceToDelete(null)}
        onConfirm={confirmDelete}
      />
    </main>
  );
}
