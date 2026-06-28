import type { PublicEventDetail } from './events';

export type RsvpAttendance = 'confirmed' | 'declined';
export type RsvpMode = 'general' | 'invitation' | 'management';

export type RsvpReceipt = {
    event: {
        name: string;
        starts_at: string;
        timezone: string;
    };
    name: string;
    status: RsvpAttendance;
    adult_companions: number;
    child_companions: number;
    companion_count: number;
    party_size: number;
    updated_at: string;
    update_url: string;
};

export type RsvpFormProps = {
    event: PublicEventDetail;
    rsvp: {
        mode: RsvpMode;
        submit_url: string;
        method: 'post' | 'patch';
        response_token: string | null;
        guest_name: string | null;
        name_locked: boolean;
        initial: {
            name: string;
            attendance: RsvpAttendance | '';
            adult_companions: number;
            child_companions: number;
        };
        receipt: RsvpReceipt | null;
    };
};

export type RsvpFormData = {
    name: string;
    attendance: RsvpAttendance | '';
    adult_companions: string;
    child_companions: string;
    response_token: string;
};
