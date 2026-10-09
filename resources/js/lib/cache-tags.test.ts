import { describe, expect, it } from 'vite-plus/test';
import {
    CACHE_TAGS,
    cacheTagForUrl,
    invalidatedBy,
    prefetchProps,
    prefetchPropsForUrl,
} from '@/lib/cache-tags';
import { dashboard } from '@/routes';
import { index as materialsIndex } from '@/routes/materials';

describe('invalidatedBy', () => {
    it('flushes materials, products and the dashboard after a material write', () => {
        expect(invalidatedBy('material')).toEqual([
            'materials',
            'products',
            'dashboard',
        ]);
    });

    it('flushes products, materials, sales and the dashboard after a product write', () => {
        expect(invalidatedBy('product')).toEqual([
            'products',
            'materials',
            'sales',
            'dashboard',
        ]);
    });

    it('flushes sales, products and the dashboard after a sale write', () => {
        expect(invalidatedBy('sale')).toEqual([
            'sales',
            'products',
            'dashboard',
        ]);
    });

    it('flushes materials, products, sales and the dashboard after a unit or category write', () => {
        expect(invalidatedBy('reference')).toEqual([
            'materials',
            'products',
            'sales',
            'dashboard',
        ]);
    });

    it('hands out a copy, so a caller cannot change the sets', () => {
        invalidatedBy('sale').push('materials');

        expect(invalidatedBy('sale')).toEqual([
            'sales',
            'products',
            'dashboard',
        ]);
    });
});

describe('prefetchProps', () => {
    it('prefetches on hover and tags the cached response', () => {
        expect(prefetchProps(CACHE_TAGS.materials)).toEqual({
            prefetch: true,
            cacheTags: ['materials'],
        });
    });

    it('accepts several tags', () => {
        expect(prefetchProps('products', 'sales').cacheTags).toEqual([
            'products',
            'sales',
        ]);
    });
});

describe('cacheTagForUrl', () => {
    it.each([
        ['/dashboard', 'dashboard'],
        ['/materials', 'materials'],
        ['/materials/create', 'materials'],
        ['/materials/5/edit', 'materials'],
        ['/materials?low_stock=1', 'materials'],
        ['/materials/?search=denim#top', 'materials'],
        ['https://invory.test/products/12', 'products'],
        ['/sales?record=1', 'sales'],
    ])('maps %s to the %s tag', (url, tag) => {
        expect(cacheTagForUrl(url)).toBe(tag);
    });

    it.each([
        '/',
        '/settings/profile',
        '/materials-archive',
        '/dashboard/reports',
        '/login',
    ])('gives %s no tag', (url) => {
        expect(cacheTagForUrl(url)).toBeNull();
    });

    it('reads a Wayfinder route as well as a string', () => {
        expect(cacheTagForUrl(materialsIndex())).toBe('materials');
        expect(cacheTagForUrl(dashboard())).toBe('dashboard');
    });
});

describe('prefetchPropsForUrl', () => {
    it('prefetches a tagged page under its tag', () => {
        expect(prefetchPropsForUrl('/products/3')).toEqual({
            prefetch: true,
            cacheTags: ['products'],
        });
    });

    it('adds nothing for a page that no write could invalidate', () => {
        expect(prefetchPropsForUrl('/settings/profile')).toEqual({});
    });
});
