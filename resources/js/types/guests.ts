export type GuestStatus = 'pending' | 'confirmed' | 'declined';

export type GuestLinks = {
    update: string;
    destroy: string;
};

export type GuestListItem = {
    name: string;
    status: GuestStatus;
    adult_companions: number;
    child_companions: number;
    companion_count: number;
    invitation_url: string;
    links: GuestLinks;
};

export type PaginatedGuests = {
    data: GuestListItem[];
    current_page: number;
    from: number | null;
    last_page: number;
    next_page_url: string | null;
    per_page: number;
    prev_page_url: string | null;
    to: number | null;
    total: number;
};

export type GuestStatusOption = {
    value: GuestStatus;
    label_key: `guests.status.${GuestStatus}`;
};

export type GuestFormData = {
    name: string;
    status: GuestStatus;
    adult_companions: number;
    child_companions: number;
};
