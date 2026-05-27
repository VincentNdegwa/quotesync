import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import type { BuilderClientOption, BuilderTemplateOption, BuilderCatalogItem, BuilderTaxOption, BuilderConfigurationUnit } from '@/types';

// Singleton state - shared across all component instances
const clients = ref<BuilderClientOption[]>([]);
const templates = ref<BuilderTemplateOption[]>([]);
const catalogItems = ref<BuilderCatalogItem[]>([]);
const taxes = ref<BuilderTaxOption[]>([]);
const units = ref<BuilderConfigurationUnit[]>([]);

const loading = ref({
    clients: false,
    templates: false,
    catalogItems: false,
    taxes: false,
    units: false,
});

const anyLoading = computed(() => Object.values(loading.value).some((v) => v));

let isInitialized = false;

export function useBuilderData() {
    
    async function uploadLogo(file: File): Promise<string> {
        const formData = new FormData();
        formData.append('file', file);

        const response = await fetch('/builder/upload-logo', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: formData,
        });

        if (!response.ok) {
            const text = await response.text();
            console.error('Upload error response:', text);
            
            try {
                const json = JSON.parse(text);
                if (json.errors?.file) {
                    const errorMessage = json.errors.file[0];
                    toast.error(`File upload failed: ${errorMessage}`);
                    throw new Error(errorMessage);
                }
                const errorMessage = json.message || 'File upload failed';
                toast.error(`File upload failed: ${errorMessage}`);
                throw new Error(errorMessage);
            } catch {
                toast.error('File upload failed');
                throw new Error('File upload failed');
            }
        }

        const data = await response.json();
        return data.url;
    }

    async function fetchClients(search?: string): Promise<void> {
        loading.value.clients = true;
        try {
            const url = search ? `/builder/clients?search=${encodeURIComponent(search)}` : '/builder/clients';
            const response = await fetch(url);
            const json = await response.json();
            clients.value = json.data;
        } catch (error) {
            console.error('Failed to fetch clients:', error);
        } finally {
            loading.value.clients = false;
        }
    }

    async function fetchTemplates(): Promise<void> {
        loading.value.templates = true;
        try {
            const response = await fetch('/builder/templates');
            const json = await response.json();
            templates.value = json.data;
        } catch (error) {
            console.error('Failed to fetch templates:', error);
        } finally {
            loading.value.templates = false;
        }
    }

    async function fetchCatalogItems(search?: string): Promise<void> {
        loading.value.catalogItems = true;
        try {
            const url = search ? `/builder/catalog-items?search=${encodeURIComponent(search)}` : '/builder/catalog-items';
            const response = await fetch(url);
            const json = await response.json();
            catalogItems.value = json.data;
        } catch (error) {
            console.error('Failed to fetch catalog items:', error);
        } finally {
            loading.value.catalogItems = false;
        }
    }

    async function fetchTaxes(): Promise<void> {
        loading.value.taxes = true;
        try {
            const response = await fetch('/builder/taxes');
            const json = await response.json();
            taxes.value = json.data;
        } catch (error) {
            console.error('Failed to fetch taxes:', error);
        } finally {
            loading.value.taxes = false;
        }
    }

    async function fetchUnits(): Promise<void> {
        loading.value.units = true;
        try {
            const response = await fetch('/builder/units');
            const json = await response.json();
            units.value = json.data;
        } catch (error) {
            console.error('Failed to fetch units:', error);
        } finally {
            loading.value.units = false;
        }
    }

    async function fetchAll(force = false): Promise<void> {
        if (isInitialized && !force) {
            return;
        }

        await Promise.all([
            fetchClients(),
            fetchTemplates(),
            fetchCatalogItems(),
            fetchTaxes(),
            fetchUnits(),
        ]);

        isInitialized = true;
    }

    function invalidate(): void {
        isInitialized = false;
    }

    return {
        clients,
        templates,
        catalogItems,
        taxes,
        units,
        loading,
        anyLoading,
        uploadLogo,
        fetchClients,
        fetchTemplates,
        fetchCatalogItems,
        fetchTaxes,
        fetchUnits,
        fetchAll,
        invalidate,
    };
}
