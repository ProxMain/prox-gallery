import * as React from "react";
import * as TabsPrimitive from "@radix-ui/react-tabs";

import { cn } from "@/lib/utils";

function Tabs({ className, ...props }) {
  return <TabsPrimitive.Root className={cn("flex flex-col gap-4", className)} {...props} />;
}

function TabsList({ className, ...props }) {
  return (
    <TabsPrimitive.List
      className={cn(
        "inline-flex h-12 items-center justify-center rounded-full border border-white/10 bg-slate-950/[0.8] p-1 text-slate-400 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]",
        className
      )}
      {...props}
    />
  );
}

function TabsTrigger({ className, ...props }) {
  return (
    <TabsPrimitive.Trigger
      className={cn(
        "inline-flex items-center justify-center whitespace-nowrap rounded-full px-4 py-2 text-sm font-medium text-slate-300 transition-all hover:text-white data-[state=active]:bg-[linear-gradient(135deg,rgba(239,68,68,0.8),rgba(249,115,22,0.82),rgba(250,204,21,0.65))] data-[state=active]:text-white data-[state=active]:shadow-[0_14px_32px_rgba(249,115,22,0.25)]",
        className
      )}
      {...props}
    />
  );
}

function TabsContent({ className, ...props }) {
  return <TabsPrimitive.Content className={cn("outline-none", className)} {...props} />;
}

export { Tabs, TabsList, TabsTrigger, TabsContent };
