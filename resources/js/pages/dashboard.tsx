import { Head } from '@inertiajs/react';
import ChatWindow from '@/components/chat';
import WorkflowAssistant from '@/components/workflow-assistant';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 md:p-6">
                <WorkflowAssistant />
            </div>
            <ChatWindow />
        </AppLayout>
    );
}