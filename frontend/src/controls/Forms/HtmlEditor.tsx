/*
 * Copyright (c) 2024. Esup - Université de Bordeaux
 *
 * This file is part of the Esup-Oasis project (https://github.com/EsupPortail/esup-oasis).
 * For full copyright and license information please view the LICENSE file distributed with the source code.
 *
 * @author Julien Lemonnier <julien.lemonnier@u-bordeaux.fr>
 */

import { EditorProvider, useCurrentEditor, useEditorState } from "@tiptap/react";
import { StarterKit } from "@tiptap/starter-kit";
import {
  BoldOutlined,
  ItalicOutlined,
  LinkOutlined,
  OrderedListOutlined,
  StrikethroughOutlined,
  UnorderedListOutlined,
} from "@ant-design/icons";
import { Button } from "antd";
import "@controls/Forms/HtmlEditor.css";
import { useCallback } from "react";

// Depuis tiptap v3, StarterKit embarque Link : il se configure donc à travers
// lui, deux extensions de même nom étant interdites.
const EXTENSIONS = [
  StarterKit.configure({
    link: {
      protocols: ["http", "https", "mailto"],
      openOnClick: false,
      autolink: true,
    },
    // Nouveauté v3, désactivée pour ne pas modifier le contenu enregistré : cette
    // extension ajoute un paragraphe vide après un document terminé par un titre
    // ou une liste, qui se retrouverait dans le HTML sérialisé.
    trailingNode: false,
  }),
];

/** Niveaux de titre proposés par la barre d'outils. */
const HEADING_LEVELS = [1, 2, 3, 4, 5, 6] as const;

function MenuBar() {
  const { editor } = useCurrentEditor();

  // Depuis tiptap v3, les hooks ne déclenchent plus de rendu à chaque
  // transaction : sans cet abonnement explicite, l'état actif des boutons et
  // leur désactivation resteraient figés sur la valeur du premier rendu.
  const state = useEditorState({
    editor,
    selector: ({ editor: e }) => ({
      isBold: e?.isActive("bold") ?? false,
      isItalic: e?.isActive("italic") ?? false,
      isStrike: e?.isActive("strike") ?? false,
      isParagraph: e?.isActive("paragraph") ?? false,
      isBulletList: e?.isActive("bulletList") ?? false,
      isOrderedList: e?.isActive("orderedList") ?? false,
      isLink: e?.isActive("link") ?? false,
      activeHeading: HEADING_LEVELS.find((level) => e?.isActive("heading", { level })) ?? null,
      canBold: e?.can().chain().focus().toggleBold().run() ?? false,
      canItalic: e?.can().chain().focus().toggleItalic().run() ?? false,
      canStrike: e?.can().chain().focus().toggleStrike().run() ?? false,
    }),
  });

  const setLink = useCallback(() => {
    const previousUrl = editor?.getAttributes("link").href;
    const url = window.prompt("URL", previousUrl);

    // cancelled
    if (url === null) {
      return;
    }

    // empty
    if (url === "") {
      editor?.chain().focus().extendMarkRange("link").unsetLink().run();

      return;
    }

    // update link
    editor?.chain().focus().extendMarkRange("link").setLink({ href: url }).run();
  }, [editor]);

  if (!editor || !state) {
    return null;
  }

  return (
    <div className="mb-1">
      <span className="mr-2">
        <Button
          onClick={() => editor.chain().focus().toggleBold().run()}
          disabled={!state.canBold}
          className={state.isBold ? "is-active" : ""}
        >
          <BoldOutlined />
        </Button>
        <Button
          onClick={() => editor.chain().focus().toggleItalic().run()}
          disabled={!state.canItalic}
          className={state.isItalic ? "is-active" : ""}
        >
          <ItalicOutlined />
        </Button>
        <Button
          onClick={() => editor.chain().focus().toggleStrike().run()}
          disabled={!state.canStrike}
          className={state.isStrike ? "is-active" : ""}
        >
          <StrikethroughOutlined />
        </Button>
      </span>
      <span className="mr-2">
        <Button
          onClick={() => editor.chain().focus().setParagraph().run()}
          className={state.isParagraph ? "is-active" : ""}
        >
          P
        </Button>
        <Button
          onClick={() => editor.chain().focus().toggleHeading({ level: 1 }).run()}
          className={state.activeHeading === 1 ? "is-active" : ""}
        >
          H1
        </Button>
        <Button
          onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
          className={state.activeHeading === 2 ? "is-active" : ""}
        >
          H2
        </Button>
        <Button
          onClick={() => editor.chain().focus().toggleHeading({ level: 3 }).run()}
          className={state.activeHeading === 3 ? "is-active" : ""}
        >
          H3
        </Button>
        <Button
          onClick={() => editor.chain().focus().toggleHeading({ level: 4 }).run()}
          className={state.activeHeading === 4 ? "is-active" : ""}
        >
          H4
        </Button>
        <Button
          onClick={() => editor.chain().focus().toggleHeading({ level: 5 }).run()}
          className={state.activeHeading === 5 ? "is-active" : ""}
        >
          H5
        </Button>
        <Button
          onClick={() => editor.chain().focus().toggleHeading({ level: 6 }).run()}
          className={state.activeHeading === 6 ? "is-active" : ""}
        >
          H6
        </Button>
      </span>
      <span className="mr-2">
        <Button
          onClick={() => editor.chain().focus().toggleBulletList().run()}
          className={state.isBulletList ? "is-active" : ""}
        >
          <UnorderedListOutlined />
        </Button>
        <Button
          onClick={() => editor.chain().focus().toggleOrderedList().run()}
          className={state.isOrderedList ? "is-active" : ""}
        >
          <OrderedListOutlined />
        </Button>
      </span>
      <span>
        <Button onClick={setLink} className={state.isLink ? "is-active" : ""}>
          <LinkOutlined />
        </Button>
      </span>
    </div>
  );
}

/** Nom accessible par défaut de la zone d'édition, à défaut de libellé fourni. */
const ARIA_LABEL_DEFAUT = "Éditeur de contenu";

export default function HtmlEditor(props: {
  value?: string;
  onChange?: (value: string) => void;
  /**
   * Nom accessible de la zone d'édition (rôle `textbox`). À renseigner avec
   * l'intitulé visible qui précède l'éditeur, pour que nom accessible et
   * libellé visuel concordent (WCAG 2.5.3).
   */
  ariaLabel?: string;
}) {
  return (
    <EditorProvider
      slotBefore={<MenuBar />}
      extensions={EXTENSIONS}
      content={props.value}
      editorProps={{
        // ProseMirror ne pose aucun nom accessible sur son `contenteditable`.
        attributes: { "aria-label": props.ariaLabel ?? ARIA_LABEL_DEFAUT },
      }}
      onUpdate={(content) => {
        props.onChange?.(content.editor.getHTML());
      }}
    >
      &nbsp;
    </EditorProvider>
  );
}
