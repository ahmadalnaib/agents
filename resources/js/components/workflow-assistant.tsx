import { useHttp } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Sparkles, ScrollText, Link as LinkIcon, LoaderCircle } from 'lucide-react';
import { advice as workflowAdvice } from '@/routes/workflow';

type WorkflowStep = {
    number: number;
    title: string;
    description: string;
    status: 'pending' | 'done';
};

type WorkflowLink = {
    title: string;
    url: string;
};

type WorkflowPayload = {
    workflow: {
        id: number;
        title: string;
        summary: string;
        status: string;
    };
    steps: WorkflowStep[];
    links: WorkflowLink[];
    raw?: string;
};

export default function WorkflowAssistant() {
    const { data, setData, post, processing, errors } = useHttp({
        problem: '',
    });

    const [payload, setPayload] = useState<WorkflowPayload | null>(null);

    const stepsCount = useMemo(() => payload?.steps.length ?? 0, [payload]);

    const getLinksForStep = (stepNumber: number): WorkflowLink[] => {
        if (!payload || payload.links.length === 0 || payload.steps.length === 0) {
            return [];
        }

        const linksPerStep = Math.max(1, Math.ceil(payload.links.length / payload.steps.length));
        const start = (stepNumber - 1) * linksPerStep;

        return payload.links.slice(start, start + linksPerStep);
    };

    const handleSubmit = () => {
        if (!data.problem.trim()) {
            return;
        }

        post(workflowAdvice.url(), {
            onSuccess: (response: WorkflowPayload) => {
                setPayload(response);
                setData('problem', '');
            },
        });
    };

    return (
        <section className="relative overflow-hidden rounded-3xl border border-amber-200/60 bg-[radial-gradient(circle_at_12%_10%,#fef3c7,transparent_35%),radial-gradient(circle_at_90%_20%,#bfdbfe,transparent_30%),linear-gradient(135deg,#fff7ed_0%,#f8fafc_55%,#ecfeff_100%)] p-6 shadow-[0_20px_80px_-40px_rgba(15,23,42,0.55)] md:p-8">
            <div className="absolute -right-10 top-8 h-36 w-36 rounded-full bg-cyan-200/40 blur-2xl" />
            <div className="absolute -left-8 bottom-0 h-32 w-32 rounded-full bg-amber-300/30 blur-2xl" />

            <div className="relative grid gap-6 lg:grid-cols-[1.2fr_1fr]">
                <div className="space-y-4">
                    <div className="inline-flex items-center gap-2 rounded-full border border-amber-300 bg-white/70 px-3 py-1 text-xs font-semibold tracking-[0.14em] text-amber-900">
                        <Sparkles className="h-3.5 w-3.5" />
                        Germany Workflow Studio
                    </div>

                    <h2 className="max-w-xl text-3xl font-black leading-tight text-slate-900 md:text-4xl">
                        Tell us your problem in Germany and get clear workflow steps with trusted links.
                    </h2>

                    <p className="max-w-2xl text-sm text-slate-600 md:text-base">
                        Write your issue once. The assistant creates a workflow, stores each step, and adds sources you can open directly.
                    </p>

                    <div className="rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm backdrop-blur">
                        <label htmlFor="problem" className="mb-2 block text-sm font-semibold text-slate-800">
                            What problem do you have in Germany?
                        </label>
                        <textarea
                            id="problem"
                            rows={5}
                            value={data.problem}
                            onChange={(e) => setData('problem', e.currentTarget.value)}
                            placeholder="Example: I moved to Berlin and I need to register my address, open health insurance, and understand tax class for my new job..."
                            className="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-amber-400 focus:bg-white"
                            disabled={processing}
                        />
                        {errors.problem && (
                            <p className="mt-2 text-xs font-medium text-rose-600">{errors.problem}</p>
                        )}

                        <div className="mt-3 flex items-center justify-between gap-3">
                            <p className="text-xs text-slate-500">
                                Your old chat stays as-is. This is a new workflow assistant.
                            </p>
                            <button
                                type="button"
                                onClick={handleSubmit}
                                disabled={processing || !data.problem.trim()}
                                className="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                Create Workflow
                            </button>
                        </div>
                    </div>
                </div>

                <div className="rounded-2xl border border-slate-200/70 bg-white/85 p-4 backdrop-blur md:p-5">
                    <div className="mb-3 flex items-center gap-2 text-slate-900">
                        <ScrollText className="h-4 w-4" />
                        <h3 className="text-sm font-bold uppercase tracking-[0.14em]">Workflow Result</h3>
                    </div>

                    {processing && (
                        <>
                            <div className="flex items-center gap-2 text-sm text-slate-600">
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                Building your workflow...
                            </div>
                            <div className="space-y-3">
                                <div className="h-4 w-2/3 animate-pulse rounded bg-slate-200" />
                                <div className="h-4 w-full animate-pulse rounded bg-slate-200" />
                                <div className="h-4 w-5/6 animate-pulse rounded bg-slate-200" />
                            </div>
                        </>
                    )}

                    {payload && (
                        <div className="space-y-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">
                                    #{payload.workflow.id} • {stepsCount} steps
                                </p>
                                <h4 className="text-xl font-extrabold text-slate-900">{payload.workflow.title}</h4>
                                {payload.workflow.summary && (
                                    <p className="mt-1 text-sm text-slate-600">{payload.workflow.summary}</p>
                                )}
                            </div>

                            <div className="space-y-3">
                                {payload.steps.map((step) => {
                                    const stepLinks = getLinksForStep(step.number);

                                    return (
                                        <article
                                            key={`${step.number}-${step.title}`}
                                            className="rounded-xl border border-slate-200 bg-slate-50/70 p-3"
                                        >
                                            <div className="flex items-center gap-2">
                                                <span className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-200 text-xs font-bold text-amber-900">
                                                    {step.number}
                                                </span>
                                                <p className="text-sm font-bold text-slate-900">{step.title}</p>
                                            </div>
                                            <p className="mt-1 text-sm text-slate-600">{step.description}</p>

                                            {stepLinks.length > 0 && (
                                                <div className="mt-3 rounded-lg border border-cyan-200 bg-cyan-50/70 p-2.5">
                                                    <div className="mb-1.5 flex items-center gap-1.5">
                                                        <LinkIcon className="h-3.5 w-3.5 text-cyan-800" />
                                                        <p className="text-xs font-bold uppercase tracking-[0.12em] text-cyan-900">
                                                            Links
                                                        </p>
                                                    </div>
                                                    <ul className="space-y-1 text-xs">
                                                        {stepLinks.map((link) => (
                                                            <li key={link.url}>
                                                                <a
                                                                    href={link.url}
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    className="break-words text-cyan-800 underline decoration-cyan-300 underline-offset-2 hover:text-cyan-900"
                                                                >
                                                                    {link.title}
                                                                </a>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}
                                        </article>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
