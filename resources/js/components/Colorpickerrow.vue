<script setup lang="ts">
import { XIcon } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const _props = defineProps<{
    label?: string;
    placeholder?: string;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
    (e: 'reset'): void;
}>();

const modelValue = defineModel<string | null>({ required: true });

const inputValue = ref<string>(modelValue.value ?? '');

watch(
    () => modelValue.value,
    (val) => {
        inputValue.value = val ?? '';
    },
);

const swatchColor = computed<string>(() => {
    const v = modelValue.value;

    if (!v) {
        return 'transparent';
    }

    return isValidHex(v) ? v : 'transparent';
});

const hasValue = computed(() => !!modelValue.value);

function isValidHex(value: string): boolean {
    return /^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(value);
}

function normalizeHex(value: string): string {
    let v = value.trim();

    if (v && !v.startsWith('#')) {
        v = `#${v}`;
    }

    return v.toUpperCase();
}

function onColorInputChange(e: Event): void {
    const val = (e.target as HTMLInputElement).value;
    modelValue.value = val;
    inputValue.value = val;
}

function onTextInput(e: Event): void {
    const raw = (e.target as HTMLInputElement).value;
    inputValue.value = raw;

    if (raw === '' || raw === '#') {
        modelValue.value = null;

        return;
    }

    const normalized = normalizeHex(raw);

    if (isValidHex(normalized)) {
        modelValue.value = normalized;
        inputValue.value = normalized;
    }
}

// Text input blur — normalize whatever is there or clear
function onTextBlur(): void {
    const raw = inputValue.value.trim();

    if (!raw || raw === '#') {
        inputValue.value = '';
        modelValue.value = null;

        return;
    }

    const normalized = normalizeHex(raw);

    if (isValidHex(normalized)) {
        inputValue.value = normalized;
        modelValue.value = normalized;
    } else {
        // Invalid on blur — revert to last known good value
        inputValue.value = modelValue.value ?? '';
    }
}

function onReset(): void {
    inputValue.value = '';
    modelValue.value = null;
    emit('reset');
}
</script>

<template>
    <div class="space-y-1.5">
        <!-- Optional label -->
        <p v-if="label" class="text-xs text-muted-foreground">
            {{ label }}
        </p>

        <div class="flex items-center gap-2">
            <!-- ── Color swatch + native color picker ──────────────────────── -->
            <div
                class="relative h-8 w-8 shrink-0 cursor-pointer overflow-hidden rounded-md border transition-shadow hover:shadow-md"
                :class="disabled ? 'cursor-not-allowed opacity-50' : ''"
                :title="modelValue ?? 'Pick a color'"
            >
                <!-- Checkerboard pattern behind swatch to show transparency -->
                <div
                    class="absolute inset-0"
                    style="
                        background-image:
                            linear-gradient(45deg, #ccc 25%, transparent 25%),
                            linear-gradient(-45deg, #ccc 25%, transparent 25%),
                            linear-gradient(45deg, transparent 75%, #ccc 75%),
                            linear-gradient(-45deg, transparent 75%, #ccc 75%);
                        background-size: 6px 6px;
                        background-position:
                            0 0,
                            0 3px,
                            3px -3px,
                            -3px 0;
                    "
                />

                <!-- Solid color fill -->
                <div
                    class="absolute inset-0"
                    :style="{ backgroundColor: swatchColor }"
                />

                <!-- The native input sits on top, fully transparent -->
                <!-- Clicking the swatch opens the native color picker -->
                <input
                    type="color"
                    :value="modelValue ?? '#000000'"
                    :disabled="disabled"
                    class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                    :title="modelValue ?? 'Pick a color'"
                    @input="onColorInputChange"
                />
            </div>

            <!-- ── Hex text input ───────────────────────────────────────────── -->
            <Input
                :value="inputValue"
                :placeholder="placeholder ?? 'None'"
                :disabled="disabled"
                class="h-8 flex-1 font-mono text-xs uppercase"
                maxlength="7"
                spellcheck="false"
                @input="onTextInput"
                @blur="onTextBlur"
            />

            <!-- ── Reset / clear button ────────────────────────────────────── -->
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 shrink-0 text-muted-foreground/50 transition-colors hover:text-destructive"
                :class="
                    hasValue ? 'opacity-100' : 'pointer-events-none opacity-30'
                "
                :disabled="disabled || !hasValue"
                :title="hasValue ? 'Clear color' : 'No color set'"
                @click="onReset"
            >
                <XIcon class="h-3.5 w-3.5" />
            </Button>
        </div>

        <!-- ── Validation hint ─────────────────────────────────────────────── -->
        <p
            v-if="inputValue && !isValidHex(inputValue) && inputValue !== '#'"
            class="text-[11px] text-destructive"
        >
            Enter a valid hex color (e.g. #2563EB)
        </p>
    </div>
</template>
