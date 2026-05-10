---
title: Installation
description: How to install dependencies and structure your app.
date: 2024-09-16
---

<Callout>

**Note:** We have the exact same installation process as [shadcn/ui](https://ui.shadcn.com/docs/installation/).

</Callout>

<Steps>

### Create project

Run the `init` command to create a new Next.js project or to setup an existing one:

```bash
npx shadcn@latest init
```

### Add components

You can now start adding components to your project.

```bash
npx shadcn@latest add @magicui/globe
```

### Import component

The command above will add the `Globe` component to your project. You can then import it like this:

```tsx {1,6} showLineNumbers
import { Globe } from "@/components/ui/globe"

export default function Home() {
  return <Globe />
}
```

</Steps>

---
title: Magic UI MCP Server
description: Learn how to use the Model Context Protocol with Magic UI.
date: 2025-04-16
---

Magic UI now has an official MCP server 🎉.

[MCP](https://modelcontextprotocol.com/) is an open protocol that standardizes how applications provide context to LLMs.

This is useful for Magic UI because you can now give your AI-assisted IDE direct access to all Magic UI components so that it can generate code with minimal errors.

<Tabs defaultValue="cli">

<TabsList>
  <TabsTrigger value="cli">CLI</TabsTrigger>
  <TabsTrigger value="manual">Manual</TabsTrigger>
</TabsList>

<TabsContent value="cli">

<Steps>

<Step>Installation</Step>

<Tabs defaultValue="cursor">
  <TabsList>
    <TabsTrigger value="cursor">Cursor</TabsTrigger>
    <TabsTrigger value="windsurf">Windsurf</TabsTrigger>
    <TabsTrigger value="claude">Claude</TabsTrigger>
    <TabsTrigger value="cline">Cline</TabsTrigger>
    <TabsTrigger value="roo-cline">Roo-Cline</TabsTrigger>
  </TabsList>
  <TabsContent value="cursor">

    ```bash
    npx @magicuidesign/cli@latest install cursor
    ```

  </TabsContent>
  <TabsContent value="windsurf">
  
    ```bash
    npx @magicuidesign/cli@latest install windsurf
    ```

  </TabsContent>
  <TabsContent value="claude">

    ```bash
    npx @magicuidesign/cli@latest install claude
    ```

  </TabsContent>
  <TabsContent value="cline">

    ```bash
    npx @magicuidesign/cli@latest install cline
    ```

  </TabsContent>
  <TabsContent value="roo-cline">
  
    ```bash
    npx @magicuidesign/cli@latest install roo-cline
    ```

  </TabsContent>
</Tabs>

<Step>Restart your IDE</Step>

</Steps>

</TabsContent>
<TabsContent value="manual">

<Steps>

<Step>Add the following to your MCP config file:</Step>

```json
{
  "mcpServers": {
    "magicuidesign-mcp": {
      "command": "npx",
      "args": ["-y", "@magicuidesign/mcp@latest"]
    }
  }
}
```

<Step>Restart your IDE</Step>

</Steps>

</TabsContent>

</Tabs>

## Usage

You can now ask your IDE to use any Magic UI component. Here are some examples:

- "Add a blur fade text animation"
- "Add a grid background"
- "Add a vertical marquee of logos"
