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
import type { SignatureBlockConfig } from '@/types';

const config = defineModel<SignatureBlockConfig>({ required: true });
</script>

<template>
    <div class="space-y-4 p-4">
        <h4 class="text-sm font-semibold">Signature</h4>
        <div class="space-y-2">
            <Label>Accept button text</Label>
            <Input v-model="config.acceptButtonText" />
        </div>
        <div class="space-y-2">
            <Label>Decline button text</Label>
            <Input v-model="config.declineButtonText" />
        </div>
        <div class="space-y-2">
            <Label>Legal text</Label>
            <Textarea v-model="config.legalText" rows="4" />
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div class="space-y-2">
                <Label>Accept button color</Label>
                <Input v-model="config.acceptButtonColor" placeholder="Optional color" class="font-mono text-sm" />
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
        <div class="grid grid-cols-1 gap-2">
            <div class="flex items-center justify-between rounded-md border px-3 py-2">
                <span class="text-sm">Show legal text</span>
                <Switch v-model="config.showLegalText" />
            </div>
            <div class="flex items-center justify-between rounded-md border px-3 py-2">
                <span class="text-sm">Require typed name</span>
                <Switch v-model="config.requireNameTyped" />
            </div>
            <div class="flex items-center justify-between rounded-md border px-3 py-2">
                <span class="text-sm">Allow draw signature</span>
                <Switch v-model="config.allowDrawSignature" />
            </div>
            <div class="flex items-center justify-between rounded-md border px-3 py-2">
                <span class="text-sm">Show timestamp</span>
                <Switch v-model="config.showTimestamp" />
            </div>
            <div class="flex items-center justify-between rounded-md border px-3 py-2">
                <span class="text-sm">Show IP address</span>
                <Switch v-model="config.showIpAddress" />
            </div>
            <div class="flex items-center justify-between rounded-md border px-3 py-2">
                <span class="text-sm">Show accepted banner</span>
                <Switch v-model="config.showAcceptedBanner" />
            </div>
            <div class="flex items-center justify-between rounded-md border px-3 py-2">
                <span class="text-sm">Show declined banner</span>
                <Switch v-model="config.showDeclinedBanner" />
            </div>
        </div>
    </div>
</template>
