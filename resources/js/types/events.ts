export type EventCoverImage = {
    url: string | null;
    mime: string | null;
    size: number | null;
    width: number | null;
    height: number | null;
};

export type EventLinks = {
    index?: string;
    create?: string;
    store?: string;
    show?: string;
    edit?: string;
    update?: string;
    destroy?: string;
    public?: string;
    guests?: string;
};

export type EventShare = {
    custom_message: string | null;
    default_message: string;
    summary: string;
    final_message: string;
    canonical_url: string;
    whatsapp_url: string;
    update_url: string;
};

export type EventSummary = {
    public_id: string;
    name: string;
    starts_at: string;
    starts_date: string;
    starts_time: string;
    timezone: string;
    location: string;
    theme: string | null;
    cover_image: EventCoverImage | null;
    links: EventLinks;
};

export type EventDetail = EventSummary & {
    description: string;
    share: EventShare;
};

export type PublicEventCoverImage = {
    url: string | null;
    width: number | null;
    height: number | null;
};

export type PublicEventDetail = {
    name: string;
    description: string;
    starts_at: string;
    timezone: string;
    location: string;
    theme: string | null;
    cover_image: PublicEventCoverImage | null;
    canonical_url: string;
    rsvp: {
        available: boolean;
        url: string | null;
    };
};

export type PublicEventMeta = {
    title: string;
    description: string;
    url: string;
    image: string | null;
};

export type PaginatedEvents = {
    data: EventSummary[];
    current_page: number;
    from: number | null;
    last_page: number;
    next_page_url: string | null;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

export type EventFormData = {
    name: string;
    description: string;
    starts_date: string;
    starts_time: string;
    timezone: string;
    location: string;
    theme: string;
    cover_image: File | null;
    remove_cover_image: boolean;
};

export type TimezoneOption = {
    value: string;
    label: string;
};
