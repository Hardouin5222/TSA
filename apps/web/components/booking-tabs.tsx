import { BedIcon, FlightIcon, PackageIcon, SteeringWheelIcon } from "@/components/ui/icons";

const tabs = [
  {
    icon: <FlightIcon />,
    title: "Ucak",
    description: "Tek yon, gidis donus, aktarmali secenekler",
    active: true,
  },
  {
    icon: <BedIcon />,
    title: "Otel",
    description: "Sehir, resort ve aile dostu konaklama",
  },
  {
    icon: <SteeringWheelIcon />,
    title: "Arac",
    description: "Havalimani teslim ve esnek iade",
  },
  {
    icon: <PackageIcon />,
    title: "Paket",
    description: "Ucak + otel ile donusum odakli bundle",
  },
];

export function BookingTabs() {
  return (
    <div className="booking-tabs" aria-label="Booking verticals">
      {tabs.map((tab) => (
        <article className={`booking-tab${tab.active ? " active" : ""}`} key={tab.title}>
          <div className={`icon-box${tab.active ? "" : " light"}`}>{tab.icon}</div>
          <div>
            <strong>{tab.title}</strong>
            <span>{tab.description}</span>
          </div>
        </article>
      ))}
    </div>
  );
}
