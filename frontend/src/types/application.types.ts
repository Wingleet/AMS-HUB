/**
 * Application types
 */

export interface Application {
  id: number;
  name: string;
  description?: string | null;
  url?: string | null;
  iconUrl?: string | null;
  databaseName?: string | null;
  isActive: boolean;
  /** True when the application is wired to the hub SSO and /sso/authorize can hand it a session. */
  supportsSso?: boolean;
  isSubscribed?: boolean;
  createdAt?: string;
  updatedAt?: string;
}
