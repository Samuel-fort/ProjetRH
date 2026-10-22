@php
    $markdownOptions = [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ];
@endphp

<div class="sirh-assistant-root" @if($ouvert) data-sirh-assistant-open @endif>
    <style>
        .sirh-assistant-root { --sirh-assistant-primary: #b7791f; }
        .sirh-assistant-button { position: fixed; right: 1.25rem; bottom: 1.25rem; z-index: 60; display: inline-flex; align-items: center; gap: .5rem; border: 0; border-radius: 999px; padding: .75rem 1rem; color: #fff; background: var(--sirh-assistant-primary); box-shadow: 0 8px 24px rgba(0,0,0,.2); cursor: pointer; font-weight: 600; }
        .sirh-assistant-panel { position: fixed; inset: 0 0 0 auto; z-index: 55; display: flex; width: min(100%, 27rem); flex-direction: column; background: #fff; color: #1f2937; box-shadow: -8px 0 30px rgba(0,0,0,.18); transform: translateX(100%); transition: transform .2s ease; }
        [data-sirh-assistant-open] .sirh-assistant-panel { transform: translateX(0); }
        .dark .sirh-assistant-panel { background: #111827; color: #f3f4f6; }
        .sirh-assistant-header { display: flex; align-items: center; justify-content: space-between; gap: .75rem; border-bottom: 1px solid rgba(148,163,184,.3); padding: 1rem; }
        .sirh-assistant-header h2 { margin: 0; font-size: 1rem; font-weight: 700; }
        .sirh-assistant-close, .sirh-assistant-clear { border: 0; background: transparent; color: inherit; cursor: pointer; }
        .sirh-assistant-clear { font-size: .75rem; opacity: .7; }
        .sirh-assistant-messages { display: flex; flex: 1; flex-direction: column; gap: .75rem; overflow-y: auto; padding: 1rem; }
        .sirh-assistant-message { max-width: 92%; border-radius: .75rem; padding: .7rem .8rem; font-size: .875rem; line-height: 1.45; }
        .sirh-assistant-message p { margin: 0 0 .45rem; }
        .sirh-assistant-message p:last-child { margin-bottom: 0; }
        .sirh-assistant-message ul, .sirh-assistant-message ol { margin: .35rem 0; padding-left: 1.2rem; }
        .sirh-assistant-message-user { align-self: flex-end; background: #fef3c7; color: #78350f; }
        .sirh-assistant-message-assistant { align-self: flex-start; background: #f1f5f9; }
        .dark .sirh-assistant-message-assistant { background: #1f2937; }
        .sirh-assistant-error { margin: 0 1rem .75rem; border-radius: .5rem; padding: .6rem .75rem; background: #fee2e2; color: #991b1b; font-size: .8rem; }
        .sirh-assistant-form { display: flex; gap: .5rem; border-top: 1px solid rgba(148,163,184,.3); padding: 1rem; }
        .sirh-assistant-input { min-width: 0; flex: 1; resize: none; border: 1px solid #cbd5e1; border-radius: .5rem; padding: .65rem .7rem; background: transparent; color: inherit; }
        .sirh-assistant-send { border: 0; border-radius: .5rem; padding: 0 .9rem; background: var(--sirh-assistant-primary); color: #fff; cursor: pointer; font-weight: 600; }
        @media (max-width: 640px) { .sirh-assistant-panel { width: 100%; } .sirh-assistant-button { right: .75rem; bottom: .75rem; } }
    </style>

    <button type="button" class="sirh-assistant-button" wire:click="toggle" aria-label="Ouvrir l’assistant RH">
        <span aria-hidden="true">✦</span>
        Assistant RH
    </button>

    <aside class="sirh-assistant-panel" aria-label="Assistant RH" @if(! $ouvert) aria-hidden="true" @endif>
        <header class="sirh-assistant-header">
            <h2>Assistant RH</h2>
            <div>
                <button type="button" class="sirh-assistant-clear" wire:click="effacerDiscussion">Effacer</button>
                <button type="button" class="sirh-assistant-close" wire:click="toggle" aria-label="Fermer l’assistant">×</button>
            </div>
        </header>

        <div class="sirh-assistant-messages" aria-live="polite">
            @forelse($messages as $message)
                <div class="sirh-assistant-message sirh-assistant-message-{{ $message['role'] === 'user' ? 'user' : 'assistant' }}">
                    @if($message['role'] === 'assistant')
                        {!! str($message['content'])->markdown($markdownOptions) !!}
                    @else
                        {{ $message['content'] }}
                    @endif
                </div>
            @empty
                <div class="sirh-assistant-message sirh-assistant-message-assistant">
                    Bonjour. Je peux expliquer le workflow RH, les statuts, les rôles et les menus à ouvrir.
                </div>
            @endforelse
        </div>

        @if($erreur)
            <div class="sirh-assistant-error" role="alert">{{ $erreur }}</div>
        @endif

        <form class="sirh-assistant-form" wire:submit="envoyer">
            <textarea class="sirh-assistant-input" wire:model="question" maxlength="500" rows="2" placeholder="Posez votre question..."></textarea>
            <button type="submit" class="sirh-assistant-send">Envoyer</button>
        </form>
    </aside>
</div>
