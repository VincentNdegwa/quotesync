<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { RichTextBlockConfig } from '@/types';

const config = defineModel<RichTextBlockConfig>({ required: true });
</script>

<template>
    <div class="space-y-4 p-4">
        <h4 class="text-sm font-semibold">Rich Text</h4>
        <div class="space-y-2">
            <Label>Content</Label>
            <Textarea v-model="config.content" rows="8" placeholder="Type rich content (HTML/Tiptap JSON for now)" />
        </div>
        <div class="space-y-2">
            <Label>Label</Label>
            <Input v-model="config.label" placeholder="Optional heading" />
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="space-y-2">
                <Label>Columns</Label>
                <Select v-model="config.columns">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="1">1</SelectItem>
                        <SelectItem :value="2">2</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="space-y-2">
                <Label>Label size</Label>
                <Select v-model="config.labelSize">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="h2">H2</SelectItem>
                        <SelectItem value="h3">H3</SelectItem>
                        <SelectItem value="h4">H4</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="space-y-2">
                <Label>Font size</Label>
                <Select v-model="config.fontSize">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="sm">Small</SelectItem>
                        <SelectItem value="md">Medium</SelectItem>
                        <SelectItem value="lg">Large</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="space-y-2">
                <Label>Column gap</Label>
                <Select v-model="config.columnGap">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="sm">Small</SelectItem>
                        <SelectItem value="md">Medium</SelectItem>
                        <SelectItem value="lg">Large</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="space-y-2">
                <Label>Padding</Label>
                <Select v-model="config.paddingSize">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="sm">Small</SelectItem>
                        <SelectItem value="md">Medium</SelectItem>
                        <SelectItem value="lg">Large</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
        <div class="space-y-2">
            <Label>Background color</Label>
            <Input v-model="config.backgroundColor" placeholder="Optional color" class="font-mono text-sm" />
        </div>
        <div v-if="config.borderLeft" class="space-y-2">
            <Label>Left border color</Label>
            <Input v-model="config.borderLeftColor" placeholder="Optional color" class="font-mono text-sm" />
        </div>
        <div class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Left border</span>
            <Switch v-model="config.borderLeft" />
        </div>
    </div>
</template>
