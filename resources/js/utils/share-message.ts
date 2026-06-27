export function buildShareMessage(message: string, summary: string, canonicalUrl: string): string {
    return [removeCanonicalUrl(message, canonicalUrl), removeCanonicalUrl(summary, canonicalUrl), canonicalUrl]
        .filter((part) => part.length > 0)
        .join('\n\n');
}

export function buildWhatsAppUrl(message: string): string {
    return `https://wa.me/?text=${encodeURIComponent(message)}`;
}

function removeCanonicalUrl(text: string, canonicalUrl: string): string {
    return text
        .replaceAll(canonicalUrl, '')
        .replace(/[ \t]+\n/g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}
