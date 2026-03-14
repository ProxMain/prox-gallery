export function InlineSearchInput({
  value,
  onChange,
  placeholder,
  ariaLabel
}) {
  return (
    <label className="block min-w-[220px]">
      <input
        type="text"
        value={value}
        onChange={(event) => onChange(event.target.value)}
        placeholder={placeholder}
        aria-label={ariaLabel}
        className="h-10 w-full rounded-md border border-slate-200 bg-white px-4 text-sm text-slate-900 transition placeholder:text-slate-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
      />
    </label>
  );
}
