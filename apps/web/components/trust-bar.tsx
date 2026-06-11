import { ClockIcon, ShieldIcon, SparkIcon } from "@/components/ui/icons";

const items = [
  {
    icon: <ShieldIcon />,
    title: "Guvenli odeme akisi",
    description: "Kisa checkout, acik fiyat, adim bazli ilerleme",
  },
  {
    icon: <ClockIcon />,
    title: "Hizli karar destekleri",
    description: "En populer tarih, en iyi paket ve son bakilanlar",
  },
  {
    icon: <SparkIcon />,
    title: "Capraz satis mantigi",
    description: "Ucak secen kullaniciya dogal sekilde otel ve transfer oneri",
  },
];

export function TrustBar() {
  return (
    <div className="trust-bar">
      {items.map((item) => (
        <article className="trust-chip" key={item.title}>
          <div className="icon-box light">{item.icon}</div>
          <div>
            <strong>{item.title}</strong>
            <span>{item.description}</span>
          </div>
        </article>
      ))}
    </div>
  );
}
