import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('navbar', () => ({
    openMenu: null,
    mobileOpen: false,
    mobileSection: null,
    searchOpen: false,
    toggleMenu(key) {
        this.openMenu = this.openMenu === key ? null : key;
    },
    openSearch() {
        this.searchOpen = true;
        this.$nextTick(() => this.$refs.searchInput?.focus());
    },
}));

Alpine.data('playerModal', (config) => ({
    open: false,
    title: config.title,
    meta: config.meta,
    type: config.type,
    tmdbId: config.tmdbId,
    imdbId: config.imdbId,
    season: 1,
    episode: 1,
    totalSeasons: 5,
    episodeButtons: Array.from({ length: 20 }, (_, i) => i + 1),
    embedBase: '/embed-proxy',
    get metaDisplay() {
        if (this.type !== 'tv') return this.meta;
        const pad = (n) => String(n).padStart(2, '0');
        return `S${pad(this.season)}E${pad(this.episode)}`;
    },
    get currentSrc() {
        const id = this.imdbId || this.tmdbId;
        const color = encodeURIComponent('#00e5a0');
        if (this.type === 'tv') {
            return `https://vaplayer.ru/embed/tv/${id}/${this.season}/${this.episode}?primaryColor=${color}&autoplay=1`;
        }
        return `https://vaplayer.ru/embed/movie/${id}?primaryColor=${color}&autoplay=1`;
    },
    init() {
        window.addEventListener('message', (e) => {
            if (e.data?.type !== 'PLAYER_EVENT') return;
            const { player_status, player_info } = e.data.data ?? {};
            if (player_status === 'completed' && player_info?.mediaType === 'tv') {
                this.episode += 1;
            }
        });
        this.$watch('open', (val) => {
            document.body.style.overflow = val ? 'hidden' : '';
        });
    },
}));

Alpine.start();
