"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

import { BedIcon, FlightIcon, PackageIcon, SteeringWheelIcon } from "@/components/ui/icons";

const tabs = [
  {
    href: "/",
    icon: <FlightIcon />,
    title: "Ucak",
    description: "Tek yon, gidis donus, aktarmali secenekler",
    matches: ["/", "/flights"],
  },
  {
    href: "/hotels?city=Antalya&check_in=2026-07-18&check_out=2026-07-22&adult_count=2&room_count=1",
    icon: <BedIcon />,
    title: "Otel",
    description: "Sehir, resort ve aile dostu konaklama",
    matches: ["/hotels"],
  },
  {
    href: "/cars?pickup_location=Antalya&pickup_date=2026-07-18&dropoff_date=2026-07-22&driver_age=30",
    icon: <SteeringWheelIcon />,
    title: "Arac",
    description: "Havalimani teslim ve esnek iade",
    matches: ["/cars"],
  },
  {
    href: "/flights?origin=IST&destination=AYT&departure_date=2026-07-18&return_date=2026-07-22&adult_count=2",
    icon: <PackageIcon />,
    title: "Paket",
    description: "Ucak + otel bundle omurgasi hazirlaniyor",
    matches: ["/packages"],
  },
];

export function BookingTabs() {
  const pathname = usePathname();

  return (
    <div className="booking-tabs" aria-label="Booking verticals">
      {tabs.map((tab) => {
        const isActive = tab.matches.includes(pathname);
        return (
          <Link className={`booking-tab${isActive ? " active" : ""}`} href={tab.href} key={tab.title}>
            <div className={`icon-box${isActive ? "" : " light"}`}>{tab.icon}</div>
            <div>
              <strong>{tab.title}</strong>
              <span>{tab.description}</span>
            </div>
          </Link>
        );
      })}
    </div>
  );
}
