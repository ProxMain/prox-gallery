import {
  ArrowUpRight,
  FolderOpen,
  Images,
  LayoutDashboard,
  PanelsTopLeft,
  Settings2,
} from "lucide-react";

const menuIconByKey = {
  dashboard: LayoutDashboard,
  "media-manager": Images,
  galleries: FolderOpen,
  settings: Settings2,
};

export function TopBar({ menuItems, activeMenu, onSelectMenu }) {
  return (
    <aside className="pg-sidebar">
      <a
        className="pg-skip"
        href="#pg-content"
        onClick={(event) => {
          event.preventDefault();
          document.getElementById("pg-content")?.focus();
        }}
      >
        Skip to content
      </a>
      <div className="pg-brand">
        <span className="pg-brand-mark">
          <PanelsTopLeft size={23} aria-hidden="true" />
        </span>
        <div>
          <strong>
            prox<span>gallery</span>
          </strong>
          <small>A place for your perspective</small>
        </div>
      </div>
      <p className="pg-nav-label">YOUR STUDIO</p>
      <nav className="pg-navigation" aria-label="Gallery workspace">
        {menuItems.map((menuItem) => {
          const Icon = menuIconByKey[menuItem.key] ?? LayoutDashboard;
          const isActive = activeMenu === menuItem.key;

          return (
            <button
              key={menuItem.key}
              type="button"
              onClick={() => onSelectMenu(menuItem.key)}
              aria-current={isActive ? "page" : undefined}
              className={`pg-nav-item ${isActive ? "is-active" : ""}`}
            >
              <Icon size={18} aria-hidden="true" />
              {menuItem.label}
              {isActive ? (
                <ArrowUpRight
                  size={14}
                  className="pg-nav-arrow"
                  aria-hidden="true"
                />
              ) : null}
            </button>
          );
        })}
      </nav>
      <div className="pg-sidebar-note">
        <span className="pg-status-dot" />
        Made for WordPress
        <p>
          Less managing.
          <br />
          More creating.
        </p>
      </div>
    </aside>
  );
}
