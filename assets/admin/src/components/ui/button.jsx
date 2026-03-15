import * as React from "react";
import { Slot } from "@radix-ui/react-slot";
import { cva } from "class-variance-authority";

import { cn } from "@/lib/utils";

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-orange-400 focus-visible:ring-offset-slate-950 disabled:pointer-events-none disabled:opacity-50",
  {
    variants: {
      variant: {
        default: "border border-orange-300/35 bg-[linear-gradient(135deg,rgba(239,68,68,0.86),rgba(249,115,22,0.92),rgba(250,204,21,0.78))] text-white shadow-[0_16px_35px_rgba(249,115,22,0.22)] hover:brightness-110",
        secondary: "border border-white/10 bg-white/[0.08] text-slate-100 hover:bg-white/[0.14]",
        outline: "border border-white/12 bg-slate-950/60 text-slate-200 hover:border-orange-300/30 hover:bg-white/8 hover:text-white",
        ghost: "text-slate-300 hover:bg-white/8 hover:text-white"
      },
      size: {
        default: "h-10 px-4 py-2",
        sm: "h-9 rounded-md px-3",
        lg: "h-11 rounded-md px-8",
        icon: "h-10 w-10"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
);

function Button({ className, variant, size, asChild = false, ...props }) {
  const Comp = asChild ? Slot : "button";

  return <Comp className={cn(buttonVariants({ variant, size, className }))} {...props} />;
}

export { Button, buttonVariants };
