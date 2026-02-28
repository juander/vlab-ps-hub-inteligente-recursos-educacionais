import { FormEvent, useEffect, useMemo, useState } from 'react';
import type { Resource, ResourcePayload } from '../types/resource';

type AiResult = {
  description: string;
  tags: string[];
};

type ResourceFormProps = {
  initialData?: Resource | null;
  loading?: boolean;
  aiLoading?: boolean;
  onSubmit: (payload: ResourcePayload) => void;
  onGenerateWithAi: (data: { title: string; type: string; url: string }) => Promise<AiResult>;
  onCancelEdit: () => void;
};

const initialState: ResourcePayload = {
  title: '',
  description: '',
  type: '',
  url: '',
  tags: [],
};

function isValidUrl(value: string): boolean {
  try {
    const parsed = new URL(value);
    return parsed.protocol === 'http:' || parsed.protocol === 'https:';
  } catch {
    return false;
  }
}

export function ResourceForm({
  initialData,
  loading = false,
  aiLoading = false,
  onSubmit,
  onGenerateWithAi,
  onCancelEdit,
}: ResourceFormProps) {
  const [form, setForm] = useState<ResourcePayload>(initialState);
  const [tagsInput, setTagsInput] = useState('');
  const [showAiHelp, setShowAiHelp] = useState(false);
  const [aiHintMessage, setAiHintMessage] = useState<string | null>(null);

  const isEditing = useMemo(() => Boolean(initialData), [initialData]);
  const canGenerateWithAi = useMemo(() => {
    return form.title.trim().length >= 3 && form.type.trim().length >= 2 && isValidUrl(form.url.trim());
  }, [form.title, form.type, form.url]);

  useEffect(() => {
    if (!initialData) {
      setForm(initialState);
      setTagsInput('');
      setAiHintMessage(null);
      return;
    }

    setForm({
      title: initialData.title,
      description: initialData.description,
      type: initialData.type,
      url: initialData.url,
      tags: initialData.tags,
    });
    setTagsInput(initialData.tags.join(', '));
    setAiHintMessage(null);
  }, [initialData]);

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    const parsedTags = tagsInput
      .split(',')
      .map((tag) => tag.trim())
      .filter(Boolean);

    onSubmit({
      ...form,
      title: form.title.trim(),
      description: form.description.trim(),
      type: form.type.trim(),
      url: form.url.trim(),
      tags: parsedTags,
    });

    if (!isEditing) {
      setForm(initialState);
      setTagsInput('');
      setAiHintMessage(null);
    }
  }

  async function handleGenerate(): Promise<void> {
    if (!canGenerateWithAi) {
      setAiHintMessage('Para usar IA, informe título, tipo e uma URL válida (http/https).');
      return;
    }

    setAiHintMessage(null);

    try {
      const result = await onGenerateWithAi({ title: form.title.trim(), type: form.type.trim(), url: form.url.trim() });
      setForm((previous) => ({
        ...previous,
        description: result.description,
        tags: result.tags,
      }));
      setTagsInput(result.tags.join(', '));
      setAiHintMessage('Sugestões geradas. Revise a descrição e as tags antes de salvar.');
    } catch {
      // Erro exibido no App.
    }
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-start justify-between gap-3">
        <div>
          <h2 className="text-lg font-semibold text-slate-900">{isEditing ? 'Editar recurso' : 'Novo recurso'}</h2>
          <p className="text-xs text-slate-500">Preencha os dados e use a IA para acelerar o cadastro.</p>
        </div>
        <button
          type="button"
          onClick={() => setShowAiHelp((current) => !current)}
          className="rounded-md border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
        >
          {showAiHelp ? 'Ocultar ajuda IA' : 'Como usar IA?'}
        </button>
      </div>

      {showAiHelp && (
        <div className="rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
          <p className="font-medium">Passo a passo</p>
          <ol className="mt-1 list-decimal space-y-1 pl-4">
            <li>Digite título, tipo e URL do conteúdo.</li>
            <li>Clique em <strong>Gerar descrição com IA</strong>.</li>
            <li>Confira descrição/tags e ajuste se necessário.</li>
            <li>Finalize em <strong>Criar recurso</strong> ou <strong>Atualizar</strong>.</li>
          </ol>
        </div>
      )}

      <div className="space-y-1">
        <label className="text-sm font-medium text-slate-700">Título</label>
        <input
          className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none"
          placeholder="Ex: Matemática financeira para iniciantes"
          value={form.title}
          onChange={(event) => setForm((prev) => ({ ...prev, title: event.target.value }))}
          required
        />
      </div>

      <div className="grid gap-3 sm:grid-cols-2">
        <div className="space-y-1">
          <label className="text-sm font-medium text-slate-700">Tipo</label>
          <input
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none"
            placeholder="Ex: vídeo, artigo, curso"
            value={form.type}
            onChange={(event) => setForm((prev) => ({ ...prev, type: event.target.value }))}
            required
          />
        </div>

        <div className="space-y-1">
          <label className="text-sm font-medium text-slate-700">URL</label>
          <input
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none"
            placeholder="https://exemplo.com/conteudo"
            type="url"
            value={form.url}
            onChange={(event) => setForm((prev) => ({ ...prev, url: event.target.value }))}
            required
          />
        </div>
      </div>

      <div className="space-y-1">
        <label className="text-sm font-medium text-slate-700">Descrição</label>
        <textarea
          className="min-h-24 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none"
          placeholder="Descrição do conteúdo (manual ou gerada por IA)"
          value={form.description}
          onChange={(event) => setForm((prev) => ({ ...prev, description: event.target.value }))}
          required
        />
      </div>

      <div className="space-y-1">
        <label className="text-sm font-medium text-slate-700">Tags</label>
        <input
          className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none"
          placeholder="Ex: matematica, juros, financas"
          value={tagsInput}
          onChange={(event) => setTagsInput(event.target.value)}
        />
        <p className="text-xs text-slate-500">Tags são palavras-chave que melhoram busca e organização.</p>
      </div>

      {aiHintMessage && (
        <div className="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
          {aiHintMessage}
        </div>
      )}

      <div className="rounded-md border border-indigo-100 bg-indigo-50 px-3 py-2 text-xs text-indigo-900">
        Requisitos da IA: título (3+), tipo e URL válida.
      </div>

      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
          onClick={handleGenerate}
          disabled={aiLoading || !canGenerateWithAi}
          title={canGenerateWithAi ? '' : 'Preencha título, tipo e URL válida para usar IA'}
        >
          {aiLoading ? 'Gerando...' : 'Gerar descrição com IA'}
        </button>

        <button
          type="submit"
          className="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-emerald-500 disabled:opacity-60"
          disabled={loading}
        >
          {loading ? 'Salvando...' : isEditing ? 'Atualizar' : 'Criar recurso'}
        </button>

        {isEditing && (
          <button
            type="button"
            className="rounded-md bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200"
            onClick={onCancelEdit}
          >
            Cancelar edição
          </button>
        )}
      </div>
    </form>
  );
}
