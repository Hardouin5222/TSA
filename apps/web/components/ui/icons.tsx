type IconProps = {
  className?: string;
};

const baseProps = {
  fill: "none",
  stroke: "currentColor",
  strokeLinecap: "round" as const,
  strokeLinejoin: "round" as const,
  strokeWidth: 1.8,
  viewBox: "0 0 24 24",
};

export function CompassIcon({ className }: IconProps) {
  return (
    <svg aria-hidden="true" className={className} height="22" width="22" {...baseProps}>
      <circle cx="12" cy="12" r="9" />
      <path d="m14.9 9.1-2.6 5.8-3.2 1.4 1.4-3.2 5.8-2.6Z" />
    </svg>
  );
}

export function FlightIcon({ className }: IconProps) {
  return (
    <svg aria-hidden="true" className={className} height="22" width="22" {...baseProps}>
      <path d="M3 13.5 10.4 12l4.2-6.5a1.7 1.7 0 0 1 3 .2l-1.2 6 3.2.7a1.5 1.5 0 0 1 0 2.9l-3.2.7 1.2 6a1.7 1.7 0 0 1-3 .2L10.4 16 3 14.5Z" />
    </svg>
  );
}

export function BedIcon({ className }: IconProps) {
  return (
    <svg aria-hidden="true" className={className} height="22" width="22" {...baseProps}>
      <path d="M4 11h16v7H4Z" />
      <path d="M4 18V7m16 11V9" />
      <path d="M7 11V8a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v3" />
    </svg>
  );
}

export function SteeringWheelIcon({ className }: IconProps) {
  return (
    <svg aria-hidden="true" className={className} height="22" width="22" {...baseProps}>
      <circle cx="12" cy="12" r="8" />
      <circle cx="12" cy="12" r="2.2" />
      <path d="M12 9.8V4.5M9.8 12H4.5m14.7 0h-5.3m-2 2.2 3.7 5.3m-7.4 0 3.7-5.3" />
    </svg>
  );
}

export function PackageIcon({ className }: IconProps) {
  return (
    <svg aria-hidden="true" className={className} height="22" width="22" {...baseProps}>
      <path d="m12 3 7 4v10l-7 4-7-4V7l7-4Z" />
      <path d="m5 7 7 4 7-4M12 11v10" />
    </svg>
  );
}

export function ShieldIcon({ className }: IconProps) {
  return (
    <svg aria-hidden="true" className={className} height="22" width="22" {...baseProps}>
      <path d="M12 3 5.5 5.8v5.9c0 4 2.5 7.7 6.5 9.3 4-1.6 6.5-5.3 6.5-9.3V5.8Z" />
      <path d="m9.2 12.2 1.9 1.9 4-4.2" />
    </svg>
  );
}

export function ClockIcon({ className }: IconProps) {
  return (
    <svg aria-hidden="true" className={className} height="22" width="22" {...baseProps}>
      <circle cx="12" cy="12" r="8.5" />
      <path d="M12 7.5v4.8l3.3 1.9" />
    </svg>
  );
}

export function SparkIcon({ className }: IconProps) {
  return (
    <svg aria-hidden="true" className={className} height="22" width="22" {...baseProps}>
      <path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8Z" />
      <path d="m5 16 .7 2 .7-2 2-.7-2-.7-.7-2-.7 2-2 .7Z" />
    </svg>
  );
}
