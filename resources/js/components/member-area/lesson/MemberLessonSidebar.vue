<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { CheckCircle, Lock, Search, X } from 'lucide-vue-next';

const props = defineProps({
    module: { type: Object, required: true },
    lessons: { type: Array, default: () => [] },
    currentLessonId: { type: Number, default: null },
    slug: { type: String, required: true },
    progressPercent: { type: Number, default: 0 },
    courseProgress: {
        type: Object,
        default: () => ({ completed: 0, total: 0 }),
    },
    isLessonCompleted: { type: Function, required: true },
    lessonUrl: { type: Function, required: true },
    mobile: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const query = ref('');
const thumbnailFailed = ref(false);

const filteredLessons = computed(() => {
    const q = query.value.trim().toLowerCase();
    const list = props.lessons || [];
    if (!q) return list;
    return list.filter((l) => (l.title || '').toLowerCase().includes(q));
});

const progressBarWidth = computed(() => {
    const { completed, total } = props.courseProgress;
    if (!total) return 0;
    return Math.min(100, Math.round((completed / total) * 100));
});

const moduleLessonProgress = computed(() => {
    const list = (props.lessons || []).filter((l) => !l.is_locked);
    const completed = list.filter((l) => props.isLessonCompleted(l)).length;
    return { completed, total: list.length };
});

const currentLessonIndex = computed(() => {
    if (!props.currentLessonId) return 0;
    const idx = (props.lessons || []).findIndex((l) => l.id === props.currentLessonId);
    return idx >= 0 ? idx + 1 : 0;
});

const showThumbnail = computed(() => Boolean(props.module.thumbnail) && !thumbnailFailed.value);
</script>

<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-2xl bg-zinc-950/60 shadow-xl shadow-black/20"
        :class="mobile ? 'max-h-[85vh]' : ''"
    >
        <div class="relative shrink-0">
            <div class="aspect-video w-full overflow-hidden bg-zinc-800">
                <img
                    v-if="showThumbnail"
                    :src="module.thumbnail"
                    :alt="module.title"
                    class="h-full w-full object-cover"
                    @error="thumbnailFailed = true"
                />
                <div v-else class="flex h-full items-center justify-center">
                    <svg class="h-12 w-12 text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent px-4 pb-4 pt-12">
                    <p v-if="module.section" class="text-xs font-medium uppercase tracking-wide text-white/60">{{ module.section.title }}</p>
                    <h2 class="mt-0.5 text-lg font-semibold leading-tight text-white">{{ module.title }}</h2>
                </div>
            </div>
            <button
                v-if="mobile"
                type="button"
                class="absolute right-3 top-3 rounded-full bg-black/60 p-2 text-white transition hover:bg-black/80"
                aria-label="Fechar lista de aulas"
                @click="emit('close')"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <div class="shrink-0 space-y-3 px-4 py-4">
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs text-zinc-400">
                    <span>{{ progressPercent }}% de progresso</span>
                    <span v-if="courseProgress.total > 0" class="tabular-nums">{{ courseProgress.completed }}/{{ courseProgress.total }}</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-zinc-700/80">
                    <div
                        class="h-full rounded-full bg-[var(--ma-primary)] transition-[width]"
                        :style="{ width: `${progressBarWidth}%` }"
                    />
                </div>
            </div>
            <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-500" />
                <input
                    v-model="query"
                    type="search"
                    placeholder="Busque por uma aula…"
                    class="w-full rounded-xl bg-white/5 py-2.5 pl-9 pr-3 text-sm text-white placeholder-zinc-500 focus:bg-white/10 focus:outline-none"
                    autocomplete="off"
                />
            </div>
        </div>

        <div
            v-if="moduleLessonProgress.total > 0"
            class="mx-3 mb-2 rounded-xl bg-[var(--ma-primary)]/10 px-4 py-3"
        >
            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--ma-primary)]">
                Módulo · {{ moduleLessonProgress.completed }}/{{ moduleLessonProgress.total }}
            </p>
            <p v-if="currentLessonIndex" class="mt-1 truncate text-sm font-semibold text-white">
                Aula {{ currentLessonIndex }} · {{ module.title }}
            </p>
        </div>

        <nav class="min-h-0 flex-1 overflow-y-auto px-2 pb-2">
            <template v-if="filteredLessons.length">
                <template v-for="(lesson, idx) in filteredLessons" :key="lesson.id">
                    <Link
                        v-if="!lesson.is_locked"
                        :href="lessonUrl(lesson.id)"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition"
                        :class="currentLessonId === lesson.id
                            ? 'bg-[var(--ma-primary)] text-white shadow-lg shadow-[var(--ma-primary)]/20'
                            : 'text-zinc-300 hover:bg-white/5 hover:text-white'"
                        @click="mobile && emit('close')"
                    >
                        <CheckCircle
                            v-if="isLessonCompleted(lesson)"
                            class="h-4 w-4 shrink-0"
                            :class="currentLessonId === lesson.id ? 'text-white' : 'text-emerald-500'"
                        />
                        <span
                            v-else
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold"
                            :class="currentLessonId === lesson.id
                                ? 'bg-white/20 text-white'
                                : 'bg-white/10 text-zinc-400 group-hover:bg-white/15'"
                        >
                            {{ idx + 1 }}
                        </span>
                        <span class="min-w-0 flex-1 truncate font-medium">{{ lesson.title || 'Sem título' }}</span>
                    </Link>
                    <div
                        v-else
                        class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm opacity-60"
                    >
                        <Lock class="h-4 w-4 shrink-0 text-zinc-500" />
                        <span class="min-w-0 flex-1 truncate text-zinc-400">{{ lesson.title || 'Sem título' }}</span>
                        <span v-if="lesson.lock_message" class="shrink-0 text-[10px] text-zinc-500">{{ lesson.lock_message }}</span>
                    </div>
                </template>
            </template>
            <p v-else-if="lessons.length" class="px-3 py-6 text-center text-sm text-zinc-500">Nenhuma aula encontrada.</p>
            <p v-else class="px-3 py-6 text-center text-sm text-zinc-500">Nenhuma aula neste módulo.</p>
        </nav>
    </div>
</template>
