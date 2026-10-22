import { useEffect, useState } from "react";
import { ImageOff } from "lucide-react";

export function MediaImage({ src, alt = "", ...props }) {
  const [failed, setFailed] = useState(false);
  useEffect(() => setFailed(false), [src]);
  return failed || !src ? (
    <span className="pg-unavailable">
      <ImageOff size={25} strokeWidth={1} />
      <small>Preview unavailable</small>
    </span>
  ) : (
    <img src={src} alt={alt} {...props} onError={() => setFailed(true)} />
  );
}
