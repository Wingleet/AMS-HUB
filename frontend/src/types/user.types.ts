/**
 * User and Authentication related types
 */

export interface User {
  id: number;
  email: string;
  username?: string | null;
  firstName: string;
  lastName: string;
  fullName: string;
  roles: string[];
  isAdmin: boolean;
  isActive: boolean;
  /** AMS database this session signed in to — the `serverdb` header. */
  amsServerDb?: string | null;
  createdAt?: string;
  lastLoginAt?: string;
  organization?: Organization | null;
}

export interface Organization {
  id: number;
  name: string;
  isActive: boolean;
}

export interface LoginFormData {
  username: string;
  password: string;
  rememberMe: boolean;
  serverdb: string;
  serverdbpass: string;
}

export interface RegisterFormData {
  email: string;
  password: string;
  firstName: string;
  lastName: string;
}

export interface LoginData {
  username: string;
  password: string;
  rememberMe?: boolean;
  /** AMS database — the `serverdb` header. Blank falls back to the server's own. */
  serverdb?: string;
  /** Optional password for that database — the `serverdbpass` header. */
  serverdbpass?: string;
}

export interface FormErrors {
  [key: string]: string;
}
