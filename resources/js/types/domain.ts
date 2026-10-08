export type Decimal = string; // decimals travel as strings (scale 4). Never do maths on them in TS.

export type Option<T extends string = string> = { value: T; label: string };

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};
export type Paginated<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};

export type MaterialCategory =
    | 'fabric'
    | 'lining'
    | 'interlining'
    | 'leather'
    | 'thread'
    | 'trim'
    | 'fastener'
    | 'label'
    | 'sole'
    | 'hardware'
    | 'packaging'
    | 'other';
export type UnitOfMeasure =
    | 'm'
    | 'cm'
    | 'yd'
    | 'sqm'
    | 'sqft'
    | 'kg'
    | 'g'
    | 'l'
    | 'ml'
    | 'pc'
    | 'pair'
    | 'roll'
    | 'cone';
export type StockStatus = 'in_stock' | 'low' | 'out';

export type Material = {
    id: number;
    sku: string;
    name: string;
    category: MaterialCategory;
    category_label: string;
    unit: UnitOfMeasure;
    unit_label: string;
    unit_cost: Decimal;
    quantity_on_hand: Decimal;
    reorder_level: Decimal | null;
    stock_value: Decimal; // quantity_on_hand × unit_cost, scale 4
    stock_status: StockStatus; // computed server-side, see rule below
    supplier_name: string | null;
    supplier_reference: string | null;
    color: string | null;
    composition: string | null;
    notes: string | null;
    created_at: string; // ISO-8601
    updated_at: string;
};

export type MaterialSort =
    | 'name'
    | '-name'
    | 'sku'
    | '-sku'
    | 'quantity_on_hand'
    | '-quantity_on_hand'
    | 'unit_cost'
    | '-unit_cost'
    | 'updated_at'
    | '-updated_at';

export type MaterialFilters = {
    search: string | null;
    category: MaterialCategory | null;
    low_stock: boolean; // true → stock_status in (low, out)
    sort: MaterialSort; // default 'name'
};

/** Fields the material form submits (strings for decimals). */
export type MaterialFormData = {
    sku: string;
    name: string;
    category: MaterialCategory | '';
    unit: UnitOfMeasure | '';
    unit_cost: string;
    quantity_on_hand: string;
    reorder_level: string;
    supplier_name: string;
    supplier_reference: string;
    color: string;
    composition: string;
    notes: string;
};

// Page props
export type MaterialsIndexProps = {
    materials: Paginated<Material>;
    filters: MaterialFilters;
    categories: Option<MaterialCategory>[];
};
export type MaterialCreateProps = {
    categories: Option<MaterialCategory>[];
    units: Option<UnitOfMeasure>[];
};
export type MaterialEditProps = MaterialCreateProps & { material: Material };
export type MaterialShowProps = { material: Material };
