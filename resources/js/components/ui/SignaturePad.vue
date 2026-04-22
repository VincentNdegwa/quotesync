<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import SignaturePad from 'signature_pad';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const props = defineProps<{
    signature: string;
    name: string;
}>();

const emit = defineEmits<{
    (e: 'update:signature', value: string): void;
    (e: 'update:name', value: string): void;
}>();

const mode = ref<'draw' | 'type'>('draw');
const typedName = ref(props.name);

const canvas = ref<HTMLCanvasElement | null>(null);
let signaturePad: SignaturePad | null = null;

onMounted(() => {
    const font = new FontFace('Dancing Script', 'url(https://fonts.gstatic.com/s/dancingscript/v24/If2cXTr6YS-zF4S-kcSWSVi_slLNVQ.woff2)');
    font.load().then((loadedFont) => {
        document.fonts.add(loadedFont);
    }).catch(() => {
    });

    if (canvas.value) {
        signaturePad = new SignaturePad(canvas.value, {
            backgroundColor: 'rgba(0,0,0,0)', // transparent to match background
            penColor: 'currentColor', // inherit from text-foreground
        });
        
        signaturePad.addEventListener("endStroke", () => {
            if (mode.value === 'draw') {
                emit('update:signature', signaturePad?.toDataURL('image/png') || '');
            }
        });
        
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        
        // Wait for next tick to ensure canvas is painted, then set penColor properly from computed style
        setTimeout(() => {
            if (canvas.value && signaturePad) {
                const style = window.getComputedStyle(canvas.value);
                signaturePad.penColor = style.color || 'rgb(0,0,0)';
            }
        }, 100);
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', resizeCanvas);
    if (signaturePad) {
        signaturePad.off();
    }
});

function resizeCanvas() {
    if (!canvas.value || !signaturePad) return;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    
    const data = signaturePad.toData();
    
    canvas.value.width = canvas.value.offsetWidth * ratio;
    canvas.value.height = canvas.value.offsetHeight * ratio;
    canvas.value.getContext("2d")?.scale(ratio, ratio);
    
    signaturePad.clear();
    signaturePad.fromData(data);
}

function clear() {
    if (signaturePad) {
        signaturePad.clear();
        emit('update:signature', '');
    }
}

function undo() {
    if (signaturePad) {
        const data = signaturePad.toData();
        if (data) {
            data.pop();
            signaturePad.clear();
            signaturePad.fromData(data);
            emit('update:signature', data.length > 0 ? signaturePad.toDataURL('image/png') : '');
        }
    }
}

watch(typedName, (newVal) => {
    emit('update:name', newVal);
    if (mode.value === 'type') {
        updateTypedSignature(newVal);
    }
});

watch(mode, (newVal) => {
    if (newVal === 'type') {
        updateTypedSignature(typedName.value);
    } else {
        emit('update:signature', signaturePad && !signaturePad.isEmpty() ? signaturePad.toDataURL('image/png') : '');
    }
});

function updateTypedSignature(text: string) {
    if (!text) {
        emit('update:signature', '');
        return;
    }
    const tempCanvas = document.createElement('canvas');
    const ctx = tempCanvas.getContext('2d');
    if (!ctx) return;
    
    tempCanvas.width = 600;
    tempCanvas.height = 200;
    
    ctx.fillStyle = 'rgba(0,0,0,0)'; // transparent
    ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
    
    ctx.font = "72px 'Dancing Script', cursive";
    // We can't use css variables directly in canvas fillText easily, but we can assume it's black for the base64,
    // or get the foreground color. To be robust, let's stick to black since signatures are usually black/dark.
    ctx.fillStyle = '#0f172a'; 
    ctx.textBaseline = 'middle';
    ctx.textAlign = 'center';
    ctx.fillText(text, tempCanvas.width / 2, tempCanvas.height / 2);
    
    emit('update:signature', tempCanvas.toDataURL('image/png'));
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex gap-2 p-1 bg-muted rounded-lg w-fit">
            <Button 
                variant="ghost" 
                size="sm"
                @click.prevent="mode = 'draw'" 
                :class="[
                    'transition-colors',
                    mode === 'draw' ? 'bg-background shadow-sm hover:bg-background' : 'text-muted-foreground hover:text-foreground'
                ]"
            >
                Draw
            </Button>
            <Button 
                variant="ghost" 
                size="sm"
                @click.prevent="mode = 'type'" 
                :class="[
                    'transition-colors',
                    mode === 'type' ? 'bg-background shadow-sm hover:bg-background' : 'text-muted-foreground hover:text-foreground'
                ]"
            >
                Type
            </Button>
        </div>

        <div v-show="mode === 'draw'" class="relative w-full rounded-md border border-input bg-background overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-ring text-foreground">
            <canvas ref="canvas" class="w-full h-48 touch-none cursor-crosshair block"></canvas>
            <div class="absolute bottom-2 right-2 flex gap-2">
                <Button 
                    variant="outline" 
                    size="sm"
                    @click.prevent="undo" 
                    class="h-7 px-2 text-xs backdrop-blur-sm bg-background/80"
                >
                    Undo
                </Button>
                <Button 
                    variant="outline" 
                    size="sm"
                    @click.prevent="clear" 
                    class="h-7 px-2 text-xs backdrop-blur-sm bg-background/80"
                >
                    Clear
                </Button>
            </div>
            <div class="absolute inset-0 pointer-events-none flex items-center justify-center" v-if="!signature && mode === 'draw'">
                <span class="text-muted-foreground text-sm font-medium italic select-none">Draw your signature here</span>
            </div>
        </div>

        <div v-show="mode === 'type'" class="flex flex-col gap-4">
            <Input 
                v-model="typedName" 
                placeholder="Type your name..." 
            />
            
            <div class="w-full h-48 rounded-md border border-input bg-background shadow-sm flex items-center justify-center p-4 overflow-hidden relative">
                <div v-if="typedName" class="text-5xl text-foreground w-full text-center truncate" style="font-family: 'Dancing Script', cursive; line-height: 1.5;">
                    {{ typedName }}
                </div>
                <div v-else class="text-muted-foreground text-sm font-medium italic select-none">
                    Preview will appear here
                </div>
            </div>
        </div>
    </div>
</template>
