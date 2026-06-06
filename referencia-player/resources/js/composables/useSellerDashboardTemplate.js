import { computed, onMounted, onUnmounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const DEMO_TEMPLATE_KEY = 'demo_template_preview';

export function useSellerDashboardTemplate() {
    const page = usePage();
    const previewOverride = ref(null);

    function readPreviewOverride() {
        if (!page.props.demo_mode?.enabled || typeof window === 'undefined') {
            return null;
        }
        const raw = localStorage.getItem(DEMO_TEMPLATE_KEY);
        if (raw === 'aurora' || raw === 'kawaii' || raw === 'default') {
            return raw;
        }
        return null;
    }

    function syncPreviewOverride() {
        previewOverride.value = readPreviewOverride();
    }

    function onPreviewChanged() {
        syncPreviewOverride();
    }

    onMounted(() => {
        syncPreviewOverride();
        if (typeof window !== 'undefined') {
            window.addEventListener('demo-template-preview-changed', onPreviewChanged);
        }
    });

    onUnmounted(() => {
        if (typeof window !== 'undefined') {
            window.removeEventListener('demo-template-preview-changed', onPreviewChanged);
        }
    });

    const templateId = computed(() => {
        if (page.props.customer_panel) {
            return 'default';
        }

        const preview = previewOverride.value ?? readPreviewOverride();
        if (preview === 'aurora' || preview === 'kawaii' || preview === 'default') {
            return preview;
        }

        const raw = page.props.seller_dashboard_template;
        if (raw === 'aurora') return 'aurora';
        if (raw === 'kawaii') return 'kawaii';
        return 'default';
    });

    const isAurora = computed(() => templateId.value === 'aurora');
    const isKawaii = computed(() => templateId.value === 'kawaii');
    const isDefault = computed(() => templateId.value === 'default');
    const isThemedShell = computed(() => isAurora.value || isKawaii.value);
    const themePrefix = computed(() => {
        if (isAurora.value) return 'aurora';
        if (isKawaii.value) return 'kawaii';
        return null;
    });
    const pageWrapperClass = computed(() => {
        if (isKawaii.value) return 'kawaii-page';
        if (isAurora.value) return 'aurora-page';
        return 'space-y-6';
    });

    return {
        templateId,
        isDefault,
        isAurora,
        isKawaii,
        isThemedShell,
        themePrefix,
        pageWrapperClass,
    };
}
