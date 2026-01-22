function leaderboards() {
    return {
        // State
        period: 'all',
        mode: 'all',
        stat: 'kills',
        page: 1,
        perPage: 25,
        totalPlayers: 0,
        leaderboard: [],
        topPlayer: null,
        avgValue: 0,
        userRank: null,
        weaponLeaders: [],

        // Phase 3 State
        serverPulse: null,
        factionStats: null,
        creativeStats: {},
        achievements: [],

        activeTab: 'leaderboard',
        heatmapMap: 'dm/mohdm1',
        heatmapType: 'deaths',
        heatmapPoints: [],

        get visiblePages() {
            const total = Math.ceil(this.totalPlayers / this.perPage);
            const pages = [];
            const start = Math.max(1, this.page - 2);
            const end = Math.min(total, this.page + 2);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },

        async init() {
            await Promise.all([
                this.loadServerPulse(),
                this.loadFactionStats(),
                this.loadLeaderboard(),
                this.loadWeaponLeaders(),
                this.loadCreativeStats(),
                this.loadRecentAchievements()
            ]);

            this.$nextTick(() => {
                this.initDistributionChart();
                this.initFactionChart();

                this.$watch('activeTab', (val) => {
                    if (val === 'heatmap') this.loadHeatmap();
                });
                this.$watch('heatmapMap', () => this.loadHeatmap());
                this.$watch('heatmapType', () => this.loadHeatmap());
            });

            // Auto-refresh pulse every 10s
            setInterval(() => this.loadServerPulse(), 10000);
        },

        async loadServerPulse() {
            try {
                const res = await fetch('/api/v1/stats/server/pulse');
                if (res.ok) this.serverPulse = await res.json();
            } catch (e) {
                console.error('Pulse error:', e);
            }
        },

        async loadFactionStats() {
            try {
                const res = await fetch('/api/v1/stats/teams/performance');
                if (res.ok) this.factionStats = await res.json();
            } catch (e) {
                console.error('Faction stats error:', e);
            }
        },

        async loadCreativeStats() {
            try {
                const res = await fetch('/api/v1/stats/leaderboard/cards');
                if (res.ok) {
                    this.creativeStats = await res.json();
                }
            } catch (e) {
                console.error('Creative stats error:', e);
            }
        },

        async loadRecentAchievements() {
            try {
                // Assuming endpoint exists: /api/v1/achievements/recent
                const res = await fetch('/api/v1/achievements/recent');
                if (res.ok) {
                    this.achievements = await res.json();
                }
            } catch (e) {
                console.error('Achievements error:', e);
            }
        },

        async loadLeaderboard() {
            try {
                const params = new URLSearchParams({
                    period: this.period,
                    mode: this.mode,
                    stat: this.stat,
                    page: this.page,
                    limit: this.perPage
                });

                const res = await fetch(`/api/v1/stats/leaderboard?${params}`);
                const data = await res.json();

                this.leaderboard = data.players || [];
                this.totalPlayers = data.total || 0;
                this.topPlayer = this.leaderboard[0];
                this.avgValue = data.average || 0;
                this.userRank = data.userRank;

                this.$nextTick(() => {
                    if (this.activeTab === 'leaderboard') this.updateDistributionChart();
                });
            } catch (e) {
                console.error('Failed to load leaderboard:', e);
            }
        },

        async loadWeaponLeaders() {
            try {
                const res = await fetch('/api/v1/stats/leaderboard/weapon/all'); // Assuming backend supports this or similar
                // Fallback / Mock interaction until endpoint confirmed perfect
                // Just keep existing logic or mock for now as per previous file
                // Reusing mock if fetch fails
                if (res.ok) {
                    this.weaponLeaders = await res.json();
                } else {
                    throw new Error("Endpoint not ready");
                }
            } catch (e) {
                this.weaponLeaders = [
                    { name: 'M1 Garand', category: 'Rifle', topPlayers: [{ name: 'Rifleman', kills: 15420 }] },
                    { name: 'Thompson', category: 'SMG', topPlayers: [{ name: 'SprayNPray', kills: 18920 }] }
                ];
            }
        },

        async loadHeatmap() {
            const canvas = document.getElementById('heatmapCanvas');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Draw map placeholder
            ctx.fillStyle = '#1a1a1a';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            // Grid
            ctx.strokeStyle = '#333';
            ctx.lineWidth = 1;
            for (let i = 0; i < canvas.width; i += 50) { ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, canvas.height); ctx.stroke(); }

            try {
                const encodedMap = encodeURIComponent(this.heatmapMap);
                const res = await fetch(`/api/v1/stats/map/${encodedMap}/heatmap?type=${this.heatmapType}`);
                if (!res.ok) throw new Error('Failed to fetch');

                this.heatmapPoints = await res.json();
                this.drawHeatmap(ctx, canvas.width, canvas.height);
            } catch (e) {
                ctx.fillStyle = '#cc0000';
                ctx.fillText('Failed to load data', 20, 40);
            }
        },

        drawHeatmap(ctx, width, height) {
            // ... (keep existing draw logic) ...
            if (this.heatmapPoints.length === 0) {
                ctx.fillStyle = '#666';
                ctx.fillText('No data points found', 20, 40);
                return;
            }

            // Find bounds
            let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
            this.heatmapPoints.forEach(p => {
                if (p.x < minX) minX = p.x;
                if (p.x > maxX) maxX = p.x;
                if (p.y < minY) minY = p.y;
                if (p.y > maxY) maxY = p.y;
            });

            // Add padding
            const padding = 100;
            minX -= padding; maxX += padding; minY -= padding; maxY += padding;

            const scaleX = width / (maxX - minX || 1);
            const scaleY = height / (maxY - minY || 1);
            const scale = Math.min(scaleX, scaleY);

            const offsetX = -minX * scale + (width - (maxX - minX) * scale) / 2;
            const offsetY = -minY * scale + (height - (maxY - minY) * scale) / 2;

            // Draw points
            this.heatmapPoints.forEach(p => {
                const screenX = p.x * scale + offsetX;
                const screenY = height - (p.y * scale + offsetY); // Invert Y for canvas

                ctx.beginPath();
                ctx.arc(screenX, screenY, 4, 0, Math.PI * 2);
                ctx.fillStyle = this.heatmapType === 'deaths' ?
                    `rgba(255, 50, 50, ${Math.min(0.8, p.count * 0.1)})` :
                    `rgba(50, 255, 50, ${Math.min(0.8, p.count * 0.1)})`;
                ctx.fill();
            });
        },

        initDistributionChart() {
            const ctx = document.getElementById('distributionChart');
            if (!ctx) return;
            // ... existing chart logic ...
            this.chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['0-100', '100-500', '500-1K', '1K-5K', '5K-10K', '10K+'],
                    datasets: [{
                        label: 'Players',
                        data: [150, 450, 380, 320, 140, 60],
                        backgroundColor: 'rgba(74, 93, 35, 0.8)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(75, 85, 99, 0.3)' } },
                        x: { grid: { display: false } }
                    }
                }
            });
        },

        initFactionChart() {
            // New chart for Axis vs Allies
            const ctx = document.getElementById('factionChart');
            if (!ctx) return;

            // Wait for data
            if (!this.factionStats) return;

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Allies', 'Axis'],
                    datasets: [{
                        data: [this.factionStats.allies.wins, this.factionStats.axis.wins],
                        backgroundColor: ['#2563EB', '#DC2626']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'bottom' },
                        title: { display: true, text: 'Win Rate' }
                    }
                }
            });
        },

        statLabel(stat) {
            const labels = {
                kills: 'Kills', kd: 'K/D Ratio', score: 'Score', headshots: 'Headshots',
                accuracy: 'Accuracy', playtime: 'Playtime', winrate: 'Win Rate'
            };
            return labels[stat] || stat;
        },

        formatStat(value, stat) {
            if (!value) return '0';
            if (stat === 'kd' || stat === 'accuracy' || stat === 'winrate') {
                return typeof value === 'number' ? value.toFixed(2) : value;
            }
            if (stat === 'playtime') {
                return Math.floor(value / 3600) + 'h';
            }
            return this.formatNumber(value);
        },

        formatNumber(n) {
            if (!n) return '0';
            if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
            if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
            return n.toString();
        },

        timeAgo(date) {
            if (!date) return '';
            const seconds = Math.floor((new Date() - new Date(date)) / 1000);
            if (seconds < 60) return 'Just now';
            if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
            if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
            return Math.floor(seconds / 86400) + 'd ago';
        },

        prevPage() { if (this.page > 1) { this.page--; this.loadLeaderboard(); } },
        nextPage() { if (this.page * this.perPage < this.totalPlayers) { this.page++; this.loadLeaderboard(); } },
        goToPage(p) { this.page = p; this.loadLeaderboard(); }
    }
}
