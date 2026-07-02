<div x-show="open" x-cloak
     class="fixed inset-0 z-[9999] bg-black/95 flex flex-col animate-fadeIn"
     @keydown.escape.window="open = false"
     @click.self="open = false">
    <div class="flex items-center gap-3 px-5 py-3.5 bg-bg-2 border-b border-border shrink-0">
        <div class="flex-1 min-w-0">
            <p class="text-[15px] font-semibold text-white truncate" x-text="title"></p>
            <p class="text-[12px] text-muted" x-text="metaDisplay"></p>
        </div>
        <button type="button" @click="open = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-surface hover:bg-red-500/80 transition-colors">✕</button>
    </div>

    <div class="flex-1 relative">
        <iframe :src="currentSrc" class="absolute inset-0 w-full h-full border-0" allowfullscreen allow="autoplay; fullscreen"></iframe>
    </div>

    <div x-show="type === 'tv'" class="bg-bg-2 border-t border-border px-5 py-3 flex gap-2 flex-wrap max-h-[110px] overflow-y-auto shrink-0">
        <select x-model.number="season" @change="episode = 1" class="bg-surface border border-border text-white text-xs px-2.5 py-1.5 rounded-md outline-none focus:border-accent">
            <template x-for="s in totalSeasons" :key="s">
                <option :value="s" x-text="'Season ' + s"></option>
            </template>
        </select>
        <template x-for="ep in episodeButtons" :key="ep">
            <button type="button" @click="episode = ep"
                    class="px-3 py-1.5 rounded-md border text-xs font-medium transition-all"
                    :class="ep === episode ? 'bg-accent border-accent text-black font-bold' : 'border-border bg-surface text-muted hover:border-accent hover:text-accent'"
                    x-text="'E' + String(ep).padStart(2, '0')"></button>
        </template>
    </div>
</div>
