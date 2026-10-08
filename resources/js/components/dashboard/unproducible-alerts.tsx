import { Link } from '@inertiajs/react';
import ProductController from '@/actions/App/Http/Controllers/ProductController';
import { Callout } from '@/components/shared/callout';
import type { UnproducibleAlert } from '@/types/domain';

type UnproducibleAlertsProps = {
    alerts: UnproducibleAlert[];
};

/**
 * One danger callout per product that cannot be produced now, naming the
 * material that stops it. The server decides which products and which
 * material (the bottleneck); this only words it. Renders nothing without alerts.
 */
export function UnproducibleAlerts({ alerts }: UnproducibleAlertsProps) {
    if (alerts.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-col gap-3">
            {alerts.map(({ product, bottleneck }) => (
                <Callout
                    key={product.id}
                    tone="danger"
                    role="alert"
                    title={`${product.name} can't be produced.`}
                    action={
                        <Link href={ProductController.show(product.id)}>
                            See product{' '}
                            <span className="sr-only">{product.name}</span>
                        </Link>
                    }
                >
                    {bottleneck.stock_status === 'out'
                        ? `${bottleneck.name} is out of stock.`
                        : `${bottleneck.name} doesn't cover one unit.`}
                </Callout>
            ))}
        </div>
    );
}
