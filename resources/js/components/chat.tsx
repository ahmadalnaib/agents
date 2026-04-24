import { parse } from "marked";
import { useState, useRef, useEffect } from "react";
import { useHttp } from "@inertiajs/react";

type ChatMessage = {
    id?: number;
    role: 'user' | 'assistant';
    text: string;
    time: string;
};

const pixelAnimationStyle = `
  @keyframes pixelBounce {
    0%, 100% {
      transform: translateY(0);
      opacity: 1;
    }
    50% {
      transform: translateY(-10px);
      opacity: 0.6;
    }
  }

  .pixel-loader {
    display: flex;
    gap: 6px;
    justify-content: center;
    align-items: flex-end;
  }

  .pixel-loader div {
    width: 8px;
    height: 8px;
    background-color: #0f172a;
    animation: pixelBounce 1.4s infinite;
  }

  .pixel-loader div:nth-child(1) {
    animation-delay: -0.32s;
  }

  .pixel-loader div:nth-child(2) {
    animation-delay: -0.16s;
  }

  .pixel-loader div:nth-child(3) {
    animation-delay: 0s;
  }
`;

function PixelLoader() {
    return (
        <>
            <style>{pixelAnimationStyle}</style>
            <div className="pixel-loader">
                <div />
                <div />
                <div />
            </div>
        </>
    );
}

function ChatHeader({ toggleId }: { toggleId: string }) {
    return (
        <header className="flex items-center justify-between border-b border-slate-200 bg-white/80 px-5 py-4 backdrop-blur">
            <div>
                <p className="text-xs uppercase tracking-[0.2em] text-slate-500">
                    Atelier Chat
                </p>
                <h2 className="text-lg font-semibold text-slate-900">
                    Style Assistant
                </h2>
            </div>
            <label
                htmlFor={toggleId}
                className="cursor-pointer rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:text-slate-900"
            >
                Close
            </label>
        </header>
    );
}

function ChatBubble({ message }: { message: ChatMessage }) {
    const isUser = message.role === 'user';
    const messageHtml = parse(message?.text);
    return (
        <div
            className={
                isUser
                    ? 'ml-auto w-full max-w-[75%]'
                    : 'mr-auto w-full max-w-[75%]'
            }
        >
            <div
                className={
                    isUser
                        ? 'rounded-2xl rounded-tr-sm bg-slate-900 px-4 py-3 text-sm text-white shadow'
                        : 'rounded-2xl rounded-tl-sm bg-white px-4 py-3 text-sm text-slate-700 shadow'
                }
            >
                <div dangerouslySetInnerHTML={{ __html: messageHtml }} />
            </div>
            <p
                className={
                    isUser
                        ? 'mt-1 text-right text-xs text-slate-400'
                        : 'mt-1 text-xs text-slate-400'
                }
            >
                {message.time}
            </p>
        </div>
    );
}

export default function ChatWindow() {
    const toggleId = 'chat-toggle';
    const [responses, setResponses] = useState<ChatMessage[]>();
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const { data, setData, post, processing } = useHttp({
        message: '',
    });

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [responses, processing]);

    function handleSend() {
        if (!data.message.trim()) return;

        setResponses((prev) => [
            ...(prev || []),
            {
                id: prev ? prev.length + 1 : 1,
                role: 'user',
                text: data.message,
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            },
        ]);

        post('/agent', {
            onSuccess: (response: any) => {
                const messageText = typeof response === 'string' ? response : response.message;
                setResponses((prev) => [
                    ...(prev || []),
                    {
                        id: prev ? prev.length + 1 : 1,
                        role: 'assistant',
                        text: messageText,
                        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                    },
                ]);
                setData('message', '');
            },
            onError: () => {
                setData('message', '');
            },
        });
    }

    const handleKeyPress = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSend();
        }
    };

    return (
        <div className="relative">
            <input id={toggleId} type="checkbox" className="peer sr-only" />
            <label
                htmlFor={toggleId}
                className="fixed bottom-6 right-6 z-40 inline-flex h-12 items-center gap-2 rounded-full bg-slate-900 px-4 text-sm font-semibold text-white shadow-lg transition hover:bg-slate-800"
            >
                Chat
                <span className="inline-flex h-2 w-2 rounded-full bg-emerald-400" />
            </label>

            <section className="fixed bottom-24 right-6 z-40 hidden w-90 max-w-[92vw] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-linear-to-br from-slate-50 via-white to-amber-50 shadow-xl peer-checked:flex">
                <ChatHeader toggleId={toggleId} />

                <div className="max-h-90 space-y-4 overflow-y-auto px-5 py-6">
                    {responses?.map((msg) => (
                        <ChatBubble key={msg.id} message={msg} />
                    ))}
                    {processing && (
                        <div className="mr-auto flex w-full max-w-[75%] items-center gap-2 rounded-2xl rounded-tl-sm bg-white px-4 py-3 text-sm text-slate-700 shadow">
                            <PixelLoader />
                        </div>
                    )}
                    <div ref={messagesEndRef} />
                </div>

                <div className="border-t border-slate-200 bg-white px-5 py-4">
                    <div className="flex items-center gap-3">
                        <input
                            type="text"
                            value={data.message}
                            onChange={(e) => setData('message', e.currentTarget.value)}
                            onKeyPress={handleKeyPress}
                            placeholder="Ask about sizing, stock, or order status..."
                            className="flex-1 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-700 outline-none disabled:opacity-50"
                            disabled={processing}
                        />
                        <button
                            onClick={handleSend}
                            disabled={processing || !data.message.trim()}
                            type="button"
                            className="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 disabled:cursor-not-allowed transition"
                        >
                            Send
                        </button>
                    </div>
                </div>
            </section>
        </div>
    );
}